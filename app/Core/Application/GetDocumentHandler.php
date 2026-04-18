<?php

namespace App\Core\Application;

use App\Models\FiscalDocument;
use App\Support\FiscalDocumentApiArray;

final class GetDocumentHandler
{
    public function handle(int $tenantId, int $documentId): ?array
    {
        $document = FiscalDocument::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($documentId)
            ->with('items')
            ->first();

        if ($document === null) {
            return null;
        }

        return FiscalDocumentApiArray::fromModel($document);
    }
}
