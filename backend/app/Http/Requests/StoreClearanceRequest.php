<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreClearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function rules(): array
    {
        return [
            'term_code' => ['required', 'string', 'max:30'],
            'idempotency_key' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]{7,99}$/',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'The request contains invalid data.',
                'correlation_id' => $this->attributes->get('correlation_id'),
                'fields' => $validator->errors()->toArray(),
            ],
        ], 422));
    }
}
