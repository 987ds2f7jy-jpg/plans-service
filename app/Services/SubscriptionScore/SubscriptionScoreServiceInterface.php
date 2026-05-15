<?php

namespace App\Services\SubscriptionScore;

use App\Models\SubscriptionScore;

interface SubscriptionScoreServiceInterface
{
    public function findAvailable(array $data): ?SubscriptionScore;

    public function use(int $subscriptionScoreId): SubscriptionScore;
}
