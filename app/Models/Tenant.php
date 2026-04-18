<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'rif',
        'trade_name',
        'fiscal_address',
        'country_id',
        'state_id',
        'city_id',
        'municipality',
        'phone',
        'email',
        'is_special_taxpayer',
        'special_taxpayer_resolution',
        'withholding_agent_number',
        'economic_activity',
        'establishment_code',
        'emission_point',
    ];

    protected function casts(): array
    {
        return [
            'is_special_taxpayer' => 'boolean',
        ];
    }

    public function fiscalDocuments(): HasMany
    {
        return $this->hasMany(FiscalDocument::class);
    }

    public function apiClients(): HasMany
    {
        return $this->hasMany(ApiClient::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
