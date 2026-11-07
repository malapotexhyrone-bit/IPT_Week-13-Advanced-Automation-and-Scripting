<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveSecureExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'in:1'],
            'message_id' => ['required', 'uuid'],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['required', 'date'],
            'sender' => ['required', 'string', 'max:80'],
            'recipient' => ['required', 'string', 'max:80'],
            'purpose' => ['required', 'string', 'max:80'],
            'nonce' => ['required', 'string', 'max:64'],
            'ciphertext' => ['required', 'string', 'max:65535'],
            'tag' => ['required', 'string', 'max:64'],
        ];
    }
}
