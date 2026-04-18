<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'tenant_id',
        'sku',
        'serial',
        'name',
        'description',
        'unit_price',
        'tax_rate',
        'currency',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'tax_rate' => 'decimal:4',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fiscalDocumentItems(): HasMany
    {
        return $this->hasMany(FiscalDocumentItem::class);
    }
}
