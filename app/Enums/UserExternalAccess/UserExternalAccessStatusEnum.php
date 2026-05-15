<?php

namespace App\Enums\UserExternalAccess;

enum UserExternalAccessStatusEnum: int
{
    case active = 1;
    case blocked = 2;
}
