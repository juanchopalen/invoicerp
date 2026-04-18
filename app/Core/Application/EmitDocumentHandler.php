<?php

namespace App\Core\Application;

use App\Core\Domain\DocumentStatus;
use App\Core\Infrastructure\DocumentHasher;
use App\Core\Infrastructure\FiscalAuditLogger;
use App\Core\Infrastructure\SequenceAllocator;
use App\DTOs\EmitDocumentInput;
use App\Models\FiscalDocument;
use App\Models\FiscalDocumentItem;
use App\Models\Tenant;
use App\Support\FiscalActor;
use App\Support\FiscalDocumentApiArray;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EmitDocumentHandler
{
    public function __construct(
        private SequenceAllocator $sequences,
        private DocumentHasher $hasher,
        private FiscalAuditLogger $audit,
    ) {}

    public function handle(
        EmitDocumentInput $input,
        FiscalActor $actor,
        ?string $correlationId = null,
        ?string $requestId = null,
    ): EmitDocumentResult {
        $tenantId = $actor->tenantId;

        $totals = $this->computeTotals($input);

        return DB::transaction(function () use ($input, $actor, $tenantId, $totals, $correlationId, $requestId) {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $existing = FiscalDocument::query()
                ->where('tenant_id', $tenantId)
                ->where('source_system', $input->sourceSystem)
                ->where('external_reference', $input->externalReference)
                ->first();

            if ($existing !== null) {
                if ($existing->idempotency_payload_hash !== $input->idempotencyHash()) {
                    $this->audit->log(
                        $actor,
                        'documents.emit.conflict',
                        ['input' => $this->inputToAudit($input)],
                        ['error' => 'idempotency_payload_mismatch'],
                        $correlationId,
                        $requestId,
                    );

                    return new EmitDocumentResult(409, [
                        'message' => 'Idempotency key already used with a different payload.',
                    ], false);
                }

                $this->audit->log(
                    $actor,
                    'documents.emit.idempotent',
                    ['input' => $this->inputToAudit($input)],
                    ['document_id' => $existing->getKey()],
                    $correlationId,
                    $requestId,
                );

                return new EmitDocumentResult(200, FiscalDocumentApiArray::fromModel($existing), true);
            }

            $documentNumber = $this->sequences->nextDocumentNumber($tenantId, $input->documentType);

            $itemModels = [];
            foreach ($input->lines as $line) {
                $itemModels[] = new FiscalDocumentItem([
                    'product_id' => $line->productId,
                    'line_number' => $line->lineNumber,
                    'description' => $line->description,
                    'qty' => $line->qty,
                    'unit_price' => $line->unitPrice,
                    'tax_rate' => $line->taxRate,
                    'line_subtotal' => $line->lineSubtotal,
                    'line_tax' => $line->lineTax,
                    'line_total' => $line->lineTotal,
                    'totals' => $line->totals,
                ]);
            }

            $document = new FiscalDocument([
                'tenant_id' => $tenantId,
                ...$input->customer->toDocumentAttributes(),
                'source_system' => $input->sourceSystem,
                'external_reference' => $input->externalReference,
                'document_number' => $documentNumber,
                'document_type' => $input->documentType,
                'status' => DocumentStatus::Issued->value,
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['tax_total'],
                'total' => $totals['total'],
                'currency' => $input->currency,
                'schema_version' => $input->schemaVersion,
                'idempotency_payload_hash' => $input->idempotencyHash(),
                'issued_at' => Carbon::now(),
            ]);

            $document->hash = $this->hasher->compute($document, $itemModels);
            $document->save();

            foreach ($itemModels as $item) {
                $item->fiscal_document_id = $document->getKey();
                $item->save();
            }

            $response = FiscalDocumentApiArray::fromModel($document->fresh(['items']));

            $this->audit->log(
                $actor,
                'documents.emit',
                ['input' => $this->inputToAudit($input)],
                ['document' => $response],
                $correlationId,
                $requestId,
            );

            return new EmitDocumentResult(201, $response, false);
        });
    }

    private function computeTotals(EmitDocumentInput $input): array
    {
        $subtotal = '0';
        $tax = '0';
        foreach ($input->lines as $line) {
            $subtotal = bcadd($subtotal, $line->lineSubtotal, 4);
            $tax = bcadd($tax, $line->lineTax, 4);
        }
        $total = bcadd($subtotal, $tax, 4);

        return [
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'total' => $total,
        ];
    }

    private function inputToAudit(EmitDocumentInput $input): array
    {
        return [
            'source_system' => $input->sourceSystem,
            'external_reference' => $input->externalReference,
            'document_type' => $input->documentType,
            'currency' => $input->currency,
            'schema_version' => $input->schemaVersion,
            'customer' => $input->customer->toIdempotencyPayload(),
            'lines' => array_map(fn ($l) => [
                'line_number' => $l->lineNumber,
                'description' => $l->description,
                'product_id' => $l->productId,
            ], $input->lines),
        ];
    }
}
