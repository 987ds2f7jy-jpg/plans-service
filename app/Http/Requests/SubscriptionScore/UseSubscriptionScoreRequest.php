<?php

namespace App\Http\Requests\SubscriptionScore;

use Illuminate\Foundation\Http\FormRequest;

class UseSubscriptionScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            score_id => [required, integer],
        ];
    }
}
