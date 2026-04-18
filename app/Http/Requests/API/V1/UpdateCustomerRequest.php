<?php

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'municipality' => ['required', 'string', 'max:128'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
