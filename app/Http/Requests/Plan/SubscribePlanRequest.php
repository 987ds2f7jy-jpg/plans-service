<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class SubscribePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'external_key' => ['required', 'email'],
            'payment_method' => ['nullable', 'in:pix,credit_card'],
            'metadata' => ['nullable', 'array'],
            'customer' => ['nullable', 'array'],
            'customer.email' => ['nullable', 'email'],
            'customer.first_name' => ['nullable', 'string', 'max:255'],
            'customer.last_name' => ['nullable', 'string', 'max:255'],
            'customer.document' => ['nullable', 'string', 'max:30'],
            'card' => ['nullable', 'array'],
            'card.token' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card.issuer_id' => ['nullable', 'string', 'max:255'],
            'card.installments' => ['required_if:payment_method,credit_card', 'nullable', 'integer', 'min:1'],
            'card.payment_method_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
