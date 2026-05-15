<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'score_id' => $this->score_id,
            'status' => $this->status,
            'score' => $this->whenLoaded('score', function () {
                return [
                    'id' => $this->score->id,
                    'specialization_id' => $this->score->specialization_id,
                    'concil_type' => $this->score->concil_type,
                ];
            }),
            'subscription' => $this->whenLoaded('subscription', function () {
                return [
                    'id' => $this->subscription->id,
                    'plan_id' => $this->subscription->plan_id,
                    'external_key' => $this->subscription->external_key,
                    'status' => $this->subscription->status,
                ];
            }),
        ];
    }
}
