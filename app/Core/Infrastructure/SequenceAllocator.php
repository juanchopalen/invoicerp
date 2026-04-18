<?php

namespace App\Core\Infrastructure;

use App\Models\TenantDocumentSequence;
use Illuminate\Database\QueryException;

final class SequenceAllocator
{
    /**
     * Must run inside an outer DB transaction. Caller should lock the tenant row first for ordering.
     */
    public function nextDocumentNumber(int $tenantId, string $documentType): string
    {
        $row = TenantDocumentSequence::query()
            ->where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->lockForUpdate()
            ->first();

        if ($row === null) {
            try {
                TenantDocumentSequence::query()->create([
                    'tenant_id' => $tenantId,
                    'document_type' => $documentType,
                    'last_number' => 0,
                ]);
            } catch (QueryException) {
                // Concurrent insert; row now exists.
            }

            $row = TenantDocumentSequence::query()
                ->where('tenant_id', $tenantId)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $next = (int) $row->last_number + 1;
        $row->forceFill(['last_number' => $next])->save();

        $pad = (int) config('invoicerp.document_number_pad', 8);

        return str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}
