<?php

namespace App\Enums\SubscriptionScore;

enum SubscriptionScoreStatusEnum: int
{
    case enable = 1;
    case used = 2;
    case disable = 3;
}
