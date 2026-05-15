<?php

namespace App\Services\Plan;

use App\Models\Subscription;

interface PsychologyPlanServiceInterface
{
    public function subscribe(string $externalKey): Subscription;

    public function createSubscriptionScores(Subscription $subscription): void;

    public function getActivePsychologyPlan();
}
