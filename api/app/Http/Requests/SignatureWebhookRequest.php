<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Payload del callback. La autenticidad ya la verificó el middleware
 * VerifyWebhookSignature; aquí solo validamos la forma.
 */
class SignatureWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\-]+$/'],
            'signer_id' => ['required', 'uuid'],
        ];
    }
}
