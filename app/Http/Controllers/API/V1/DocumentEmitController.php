<?php

namespace App\Http\Controllers\API\V1;

use App\Core\Application\EmitDocumentHandler;
use App\DTOs\EmitCustomerInput;
use App\DTOs\EmitDocumentInput;
use App\DTOs\EmitDocumentLineInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\EmitDocumentRequest;
use App\Models\Customer;
use App\Support\FiscalActor;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

final class DocumentEmitController extends Controller
{
    public function store(EmitDocumentRequest $request, EmitDocumentHandler $handler): JsonResponse
    {
        /** @var FiscalActor $actor */
        $actor = $request->attributes->get('fiscal_actor');

        $lines = [];
        foreach ($request->validated('items') as $row) {
            $productId = $row['product_id'] ?? null;
            $lines[] = new EmitDocumentLineInput(
                lineNumber: (int) $row['line_number'],
                description: (string) $row['description'],
                qty: (string) $row['qty'],
                unitPrice: (string) $row['unit_price'],
                taxRate: (string) $row['tax_rate'],
                lineSubtotal: (string) $row['line_subtotal'],
                lineTax: (string) $row['line_tax'],
                lineTotal: (string) $row['line_total'],
                totals: isset($row['totals']) && is_array($row['totals']) ? $row['totals'] : null,
                productId: $productId !== null ? (int) $productId : null,
            );
        }

        $customerInput = $this->makeEmitCustomerInput($request);

        $input = new EmitDocumentInput(
            sourceSystem: (string) $request->validated('source_system'),
            externalReference: (string) $request->validated('external_reference'),
            documentType: (string) $request->validated('document_type'),
            currency: strtoupper((string) $request->validated('currency')),
            schemaVersion: (int) ($request->validated('schema_version') ?? config('invoicerp.default_schema_version', 1)),
            customer: $customerInput,
            lines: $lines,
        );

        $result = $handler->handle(
            $input,
            $actor,
            $request->attributes->get('correlation_id'),
            $request->attributes->get('request_id'),
        );

        return response()->json($result->data, $result->httpStatus);
    }

    private function makeEmitCustomerInput(EmitDocumentRequest $request): EmitCustomerInput
    {
        $validated = $request->validated();
        $tenantId = TenantContext::requireId();

        if (isset($validated['customer_id'])) {
            $customer = Customer::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail((int) $validated['customer_id']);

            return EmitCustomerInput::fromCustomer($customer);
        }

        $c = $validated['customer'];
        $country = isset($c['country']) && is_string($c['country']) && $c['country'] !== ''
            ? strtoupper($c['country'])
            : 'VE';

        return new EmitCustomerInput(
            customerId: null,
            legalName: (string) $c['legal_name'],
            taxId: (string) $c['tax_id'],
            address: (string) $c['address'],
            municipality: (string) $c['municipality'],
            state: (string) $c['state'],
            city: isset($c['city']) ? (string) $c['city'] : null,
            email: isset($c['email']) ? (string) $c['email'] : null,
            phone: isset($c['phone']) ? (string) $c['phone'] : null,
            country: $country,
        );
    }
}
