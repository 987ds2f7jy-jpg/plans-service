<?php

namespace App\Repositories\SubscriptionScore;

use App\Models\SubscriptionScore;
use App\Repositories\BaseRepository;

class SubscriptionScoreRepository extends BaseRepository implements SubscriptionScoreRepositoryInterface
{
    public function __construct(SubscriptionScore $model)
    {
        parent::__construct($model);
    }
}
