<?php

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CancelDocumentRequest extends FormRequest
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
            'document_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'source_system' => ['sometimes', 'nullable', 'string', 'max:128'],
            'external_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasId = $this->filled('document_id');
            $hasPair = $this->filled('source_system') && $this->filled('external_reference');
            if (! $hasId && ! $hasPair) {
                $validator->errors()->add('document_id', 'Provide document_id or source_system with external_reference.');
            }
        });
    }
}
