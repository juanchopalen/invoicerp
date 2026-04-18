<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\FiscalDocument;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DocumentIndexController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 25), 100);
        $documents = FiscalDocument::query()
            ->where('tenant_id', TenantContext::requireId())
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($documents);
    }
}
