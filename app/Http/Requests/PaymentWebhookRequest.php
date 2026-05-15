<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'external_id' => ['required', 'string'],
            'provider' => ['nullable', 'string'],
            'provider_payment_id' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'amount' => ['nullable', 'numeric'],
            'paid_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
