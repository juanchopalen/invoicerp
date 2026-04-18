<?php

namespace App\Core\Application;

use App\Core\Domain\DocumentStatus;
use App\Core\Infrastructure\FiscalAuditLogger;
use App\DTOs\CancelDocumentInput;
use App\Models\FiscalDocument;
use App\Support\FiscalActor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CancelDocumentHandler
{
    public function __construct(
        private FiscalAuditLogger $audit,
    ) {}

    public function handle(
        CancelDocumentInput $input,
        FiscalActor $actor,
        ?string $correlationId = null,
        ?string $requestId = null,
    ): array {
        return DB::transaction(function () use ($input, $actor, $correlationId, $requestId) {
            $document = $this->resolveDocument($input, $actor->tenantId);

            if ($document === null) {
                $this->audit->log(
                    $actor,
                    'documents.cancel.not_found',
                    ['input' => array_filter([
                        'document_id' => $input->documentId,
                        'source_system' => $input->sourceSystem,
                        'external_reference' => $input->externalReference,
                    ])],
                    ['error' => 'not_found'],
                    $correlationId,
                    $requestId,
                );

                return ['status' => 404, 'body' => ['message' => 'Document not found.']];
            }

            if ($document->status === DocumentStatus::Cancelled->value) {
                $this->audit->log(
                    $actor,
                    'documents.cancel.idempotent',
                    ['document_id' => $document->getKey()],
                    ['status' => 'already_cancelled'],
                    $correlationId,
                    $requestId,
                );

                return ['status' => 200, 'body' => $this->documentToArray($document)];
            }

            if ($document->status !== DocumentStatus::Issued->value) {
                $this->audit->log(
                    $actor,
                    'documents.cancel.invalid_state',
                    ['document_id' => $document->getKey(), 'status' => $document->status],
                    ['error' => 'invalid_state'],
                    $correlationId,
                    $requestId,
                );

                return ['status' => 422, 'body' => ['message' => 'Only issued documents can be cancelled.']];
            }

            $document->forceFill([
                'status' => DocumentStatus::Cancelled->value,
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => $input->reason,
            ])->save();

            $payload = $this->documentToArray($document->fresh());

            $this->audit->log(
                $actor,
                'documents.cancel',
                [
                    'document_id' => $document->getKey(),
                    'reason' => $input->reason,
                ],
                ['document' => $payload],
                $correlationId,
                $requestId,
            );

            return ['status' => 200, 'body' => $payload];
        });
    }

    private function resolveDocument(CancelDocumentInput $input, int $tenantId): ?FiscalDocument
    {
        if ($input->documentId !== null) {
            return FiscalDocument::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($input->documentId)
                ->first();
        }

        if ($input->sourceSystem !== null && $input->externalReference !== null) {
            return FiscalDocument::query()
                ->where('tenant_id', $tenantId)
                ->where('source_system', $input->sourceSystem)
                ->where('external_reference', $input->externalReference)
                ->first();
        }

        return null;
    }

    private function documentToArray(FiscalDocument $document): array
    {
        $document->loadMissing('items');

        return [
            'id' => $document->getKey(),
            'tenant_id' => $document->tenant_id,
            'source_system' => $document->source_system,
            'external_reference' => $document->external_reference,
            'document_number' => $document->document_number,
            'document_type' => $document->document_type,
            'status' => $document->status,
            'subtotal' => $document->subtotal,
            'tax_total' => $document->tax_total,
            'total' => $document->total,
            'currency' => $document->currency,
            'schema_version' => $document->schema_version,
            'hash' => $document->hash,
            'issued_at' => $document->issued_at?->toIso8601String(),
            'cancelled_at' => $document->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $document->cancellation_reason,
            'items' => $document->items->sortBy('line_number')->values()->map(fn ($i) => [
                'line_number' => $i->line_number,
                'description' => $i->description,
                'qty' => $i->qty,
                'unit_price' => $i->unit_price,
                'tax_rate' => $i->tax_rate,
                'line_subtotal' => $i->line_subtotal,
                'line_tax' => $i->line_tax,
                'line_total' => $i->line_total,
                'totals' => $i->totals,
            ])->all(),
        ];
    }
}
