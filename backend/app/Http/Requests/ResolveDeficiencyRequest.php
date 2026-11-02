<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResolveDeficiencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_note' => ['required', 'string', 'min:5', 'max:1000'],
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
