<?php

namespace App\Repositories\SubscriptionMember;

use App\Models\SubscriptionMember;
use App\Repositories\BaseRepository;

class SubscriptionMemberRepository extends BaseRepository implements SubscriptionMemberRepositoryInterface
{
    public function __construct(SubscriptionMember $model)
    {
        parent::__construct($model);
    }
}
