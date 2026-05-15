<?php

namespace App\Services\Plan;

use App\Models\Subscription;
use App\Models\SubscriptionMember;

interface FamilyPlanServiceInterface
{
    public function subscribe(string $externalKey): Subscription;

    public function createSubscriptionScores(Subscription $subscription): void;

    public function getActiveFamilyPlan();

    public function addMember(string $holderExternalKey, int $subscriptionId, string $externalKey): SubscriptionMember;
}
