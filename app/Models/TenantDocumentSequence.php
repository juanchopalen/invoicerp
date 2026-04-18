<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDocumentSequence extends Model
{
    protected $fillable = [
        'tenant_id',
        'document_type',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
