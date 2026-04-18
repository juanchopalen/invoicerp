<?php

namespace App\Core\Infrastructure;

use App\Models\AuditLog;
use App\Support\FiscalActor;

final class FiscalAuditLogger
{
    public function log(
        FiscalActor $actor,
        string $action,
        ?array $payload,
        ?array $response,
        ?string $correlationId = null,
        ?string $requestId = null,
    ): void {
        AuditLog::query()->create([
            'tenant_id' => $actor->tenantId,
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'action' => $action,
            'correlation_id' => $correlationId,
            'request_id' => $requestId,
            'payload' => $payload,
            'response' => $response,
            'created_at' => now(),
        ]);
    }
}
