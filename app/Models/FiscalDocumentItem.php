<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalDocumentItem extends Model
{
    protected $fillable = [
        'fiscal_document_id',
        'product_id',
        'line_number',
        'description',
        'qty',
        'unit_price',
        'tax_rate',
        'line_subtotal',
        'line_tax',
        'line_total',
        'totals',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:6',
            'unit_price' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'line_tax' => 'decimal:4',
            'line_total' => 'decimal:4',
            'totals' => 'array',
        ];
    }

    public function fiscalDocument(): BelongsTo
    {
        return $this->belongsTo(FiscalDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
