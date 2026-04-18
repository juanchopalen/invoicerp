<?php

namespace App\Support;

use App\Models\FiscalDocument;
use App\Models\FiscalDocumentItem;

final class FiscalDocumentApiArray
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(FiscalDocument $document): array
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
            'customer' => [
                'customer_id' => $document->customer_id,
                'legal_name' => $document->customer_legal_name,
                'tax_id' => $document->customer_tax_id,
                'email' => $document->customer_email,
                'phone' => $document->customer_phone,
                'address' => $document->customer_address,
                'city' => $document->customer_city,
                'municipality' => $document->customer_municipality,
                'state' => $document->customer_state,
                'country' => $document->customer_country,
            ],
            'items' => $document->items->sortBy('line_number')->values()->map(fn (FiscalDocumentItem $i) => [
                'line_number' => $i->line_number,
                'product_id' => $i->product_id,
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
