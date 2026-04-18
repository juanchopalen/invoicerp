<?php

namespace App\Core\Infrastructure;

use App\Models\FiscalDocument;
use App\Models\FiscalDocumentItem;

final class DocumentHasher
{
    public function compute(FiscalDocument $document, iterable $items): string
    {
        $lines = [];
        foreach ($items as $item) {
            if (! $item instanceof FiscalDocumentItem) {
                continue;
            }
            $lines[] = [
                'line_number' => (int) $item->line_number,
                'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                'description' => (string) $item->description,
                'qty' => (string) $item->qty,
                'unit_price' => (string) $item->unit_price,
                'tax_rate' => (string) $item->tax_rate,
                'line_subtotal' => (string) $item->line_subtotal,
                'line_tax' => (string) $item->line_tax,
                'line_total' => (string) $item->line_total,
            ];
        }

        $canonical = [
            'schema_version' => (int) $document->schema_version,
            'tenant_id' => (int) $document->tenant_id,
            'customer_id' => $document->customer_id !== null ? (int) $document->customer_id : null,
            'customer_legal_name' => $document->customer_legal_name,
            'customer_tax_id' => $document->customer_tax_id,
            'customer_email' => $document->customer_email,
            'customer_phone' => $document->customer_phone,
            'customer_address' => $document->customer_address,
            'customer_city' => $document->customer_city,
            'customer_municipality' => $document->customer_municipality,
            'customer_state' => $document->customer_state,
            'customer_country' => $document->customer_country,
            'source_system' => (string) $document->source_system,
            'external_reference' => (string) $document->external_reference,
            'document_number' => (string) $document->document_number,
            'document_type' => (string) $document->document_type,
            'status' => (string) $document->status,
            'subtotal' => (string) $document->subtotal,
            'tax_total' => (string) $document->tax_total,
            'total' => (string) $document->total,
            'currency' => (string) $document->currency,
            'items' => $lines,
        ];

        ksort($canonical);

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }
}
