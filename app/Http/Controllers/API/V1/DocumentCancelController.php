<?php

namespace App\Http\Controllers\API\V1;

use App\Core\Application\CancelDocumentHandler;
use App\DTOs\CancelDocumentInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\CancelDocumentRequest;
use App\Support\FiscalActor;
use Illuminate\Http\JsonResponse;

final class DocumentCancelController extends Controller
{
    public function store(CancelDocumentRequest $request, CancelDocumentHandler $handler): JsonResponse
    {
        /** @var FiscalActor $actor */
        $actor = $request->attributes->get('fiscal_actor');

        $input = new CancelDocumentInput(
            documentId: $request->has('document_id') ? (int) $request->validated('document_id') : null,
            sourceSystem: $request->validated('source_system') ?? null,
            externalReference: $request->validated('external_reference') ?? null,
            reason: (string) $request->validated('reason'),
        );

        $result = $handler->handle(
            $input,
            $actor,
            $request->attributes->get('correlation_id'),
            $request->attributes->get('request_id'),
        );

        return response()->json($result['body'], $result['status']);
    }
}
