<?php

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'sku' => ['nullable', 'string', 'max:64'],
            'serial' => ['nullable', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'numeric'],
            'tax_rate' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'unit' => ['nullable', 'string', 'max:32'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency') && is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper($this->input('currency'))]);
        }
        if ($this->input('sku') === '') {
            $this->merge(['sku' => null]);
        }
        if ($this->input('serial') === '') {
            $this->merge(['serial' => null]);
        }
    }
}
