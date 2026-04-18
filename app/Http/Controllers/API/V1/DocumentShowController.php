<?php

namespace App\Http\Controllers\API\V1;

use App\Core\Application\GetDocumentHandler;
use App\Http\Controllers\Controller;
use App\Support\FiscalActor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DocumentShowController extends Controller
{
    public function show(Request $request, string $id, GetDocumentHandler $handler): JsonResponse
    {
        /** @var FiscalActor $actor */
        $actor = $request->attributes->get('fiscal_actor');

        $document = $handler->handle($actor->tenantId, (int) $id);
        if ($document === null) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        return response()->json($document);
    }
}
