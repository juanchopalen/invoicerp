<?php

namespace App\Http\Requests\API\V1;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmitDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::requireId();

        $base = [
            'source_system' => ['required', 'string', 'max:128'],
            'external_reference' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:64'],
            'currency' => ['required', 'string', 'size:3'],
            'schema_version' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_number' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.qty' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'items.*.unit_price' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'items.*.tax_rate' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'items.*.line_subtotal' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'items.*.line_tax' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'items.*.line_total' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'items.*.totals' => ['nullable', 'array'],
        ];

        if (! $this->filled('customer_id')) {
            return array_merge($base, [
                'customer' => ['required', 'array'],
                'customer.legal_name' => ['required', 'string', 'max:255'],
                'customer.tax_id' => ['required', 'string', 'max:32'],
                'customer.address' => ['required', 'string', 'max:1000'],
                'customer.municipality' => ['required', 'string', 'max:128'],
                'customer.state' => ['required', 'string', 'max:128'],
                'customer.city' => ['nullable', 'string', 'max:128'],
                'customer.email' => ['nullable', 'email', 'max:255'],
                'customer.phone' => ['nullable', 'string', 'max:64'],
                'customer.country' => ['nullable', 'string', 'size:2', Rule::exists('countries', 'iso2')],
            ]);
        }

        return $base;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.line_number.distinct' => 'Cada ítem debe tener un line_number distinto dentro del documento; hay números de línea repetidos.',
        ];
    }
}
