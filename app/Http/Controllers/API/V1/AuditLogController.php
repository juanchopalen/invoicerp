<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $logs = AuditLog::query()
            ->where('tenant_id', TenantContext::requireId())
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($logs);
    }

    public function show(string $audit_log): JsonResponse
    {
        $record = AuditLog::query()
            ->where('tenant_id', TenantContext::requireId())
            ->whereKey($audit_log)
            ->firstOrFail();

        return response()->json(['data' => $record]);
    }
}
