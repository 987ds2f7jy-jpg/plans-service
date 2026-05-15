<?php

namespace App\Services\Plan;

use App\Models\Subscription;

interface WeightLossPlanServiceInterface
{
    public function subscribe(string $externalKey): Subscription;

    public function createSubscriptionScores(Subscription $subscription): void;

    public function syncNutritionExternalAccess(Subscription $subscription): void;

    public function getActiveWeightLossPlan();
}
