<?php

namespace App\Enums\Subscription;

enum SubscriptionStatusEnum: int
{
    case active = 1;
    case inactive = 2;
    case cancelled = 3;
    case pending = 4;
}
