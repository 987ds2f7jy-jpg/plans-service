<?php

namespace App\Repositories\Subscription;

use App\Models\Subscription;
use App\Repositories\BaseRepository;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }
}
