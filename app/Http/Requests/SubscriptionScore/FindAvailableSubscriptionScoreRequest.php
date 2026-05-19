<?php

namespace App\Http\Requests\SubscriptionScore;

use Illuminate\Foundation\Http\FormRequest;

class FindAvailableSubscriptionScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer'],
            'external_key' => ['required', 'email'],
            'specialization_id' => ['required', 'integer'],
        ];
    }
}
