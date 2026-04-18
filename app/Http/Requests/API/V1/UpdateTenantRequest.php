<?php

namespace App\Http\Requests\API\V1;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
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
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant->getKey())],
            'legal_name' => ['required', 'string', 'max:255'],
            'rif' => ['required', 'string', 'max:32', Rule::unique('tenants', 'rif')->ignore($tenant->getKey())],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'fiscal_address' => ['required', 'string'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'municipality' => ['required', 'string', 'max:128'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'is_special_taxpayer' => ['required', 'boolean'],
            'special_taxpayer_resolution' => ['nullable', 'string', 'max:255'],
            'withholding_agent_number' => ['nullable', 'string', 'max:255'],
            'economic_activity' => ['nullable', 'string', 'max:255'],
            'establishment_code' => ['nullable', 'string', 'max:10'],
            'emission_point' => ['nullable', 'string', 'max:10'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $v = $this->input('is_special_taxpayer');
        if ($v === 0 || $v === '0' || $v === false || $v === 'false') {
            $this->merge(['is_special_taxpayer' => false]);
        } elseif ($v === 1 || $v === '1' || $v === true || $v === 'true') {
            $this->merge(['is_special_taxpayer' => true]);
        }
    }
}
