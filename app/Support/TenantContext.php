<?php

namespace App\Support;

final class TenantContext
{
    private static ?int $tenantId = null;

    public static function set(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function id(): ?int
    {
        return self::$tenantId;
    }

    public static function requireId(): int
    {
        $id = self::$tenantId;
        if ($id === null) {
            throw new \RuntimeException('Tenant context is not set.');
        }

        return $id;
    }
}
