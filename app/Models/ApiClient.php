<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'api_key',
        'key_prefix',
        'status',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return array{0: string, 1: string} [plain_key, display_hint]
     */
    public static function generateKeyPair(): array
    {
        $prefix = Str::lower(Str::random(12));
        $secret = Str::random(40);
        $plain = $prefix.'.'.$secret;

        return [$plain, $prefix];
    }

    public static function hashKey(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Find active client by full bearer key (prefix.secret).
     */
    public static function findByPlainKey(string $plain): ?self
    {
        if (! str_contains($plain, '.')) {
            return null;
        }
        [$prefix] = explode('.', $plain, 2);
        $hash = self::hashKey($plain);

        return self::query()
            ->where('status', 'active')
            ->where('key_prefix', $prefix)
            ->where('api_key', $hash)
            ->first();
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }
}
