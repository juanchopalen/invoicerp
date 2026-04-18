<?php

namespace App\Support;

use App\Models\ApiClient;
use App\Models\User;

final readonly class FiscalActor
{
    public function __construct(
        public string $type,
        public int $id,
        public int $tenantId,
    ) {}

    public static function fromUser(User $user): self
    {
        $tenantId = $user->tenant_id;
        if ($tenantId === null) {
            throw new \InvalidArgumentException('User has no tenant_id.');
        }

        return new self('user', (int) $user->getKey(), (int) $tenantId);
    }

    public static function fromApiClient(ApiClient $client): self
    {
        return new self('api_client', (int) $client->getKey(), (int) $client->tenant_id);
    }
}
