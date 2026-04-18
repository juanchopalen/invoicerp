<?php

namespace App\Models;

use App\Core\Domain\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalDocument extends Model
{
    protected static function booted(): void
    {
        static::updating(function (FiscalDocument $document): void {
            if ($document->getOriginal('status') !== DocumentStatus::Issued->value) {
                return;
            }

            $allowedWhileIssued = ['status', 'cancelled_at', 'cancellation_reason', 'updated_at'];
            $dirty = array_keys($document->getDirty());
            $forbidden = array_diff($dirty, $allowedWhileIssued);
            if ($forbidden !== []) {
                throw new \LogicException('Issued fiscal documents are immutable except for cancellation.');
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'customer_legal_name',
        'customer_tax_id',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_city',
        'customer_municipality',
        'customer_state',
        'customer_country',
        'source_system',
        'external_reference',
        'document_number',
        'document_type',
        'status',
        'subtotal',
        'tax_total',
        'total',
        'currency',
        'schema_version',
        'idempotency_payload_hash',
        'issued_at',
        'cancelled_at',
        'cancellation_reason',
        'hash',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'total' => 'decimal:4',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FiscalDocumentItem::class);
    }
}
