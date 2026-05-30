<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateExternalPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'external_key' => ['required', 'email'],
            'plan_code' => ['required', Rule::in(['psychology', 'weight_loss', 'family'])],
            'external_payment_reference' => ['required', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'customer' => ['required', 'array'],
            'customer.email' => ['nullable', 'email'],
            'customer.first_name' => ['nullable', 'string', 'max:255'],
            'customer.last_name' => ['nullable', 'string', 'max:255'],
            'customer.document' => ['nullable', 'string', 'max:30'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
