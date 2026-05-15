<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class AddFamilyPlanMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            holder_external_key => [required, email],
            subscription_id => [required, integer],
            external_key => [required, email],
        ];
    }
}
