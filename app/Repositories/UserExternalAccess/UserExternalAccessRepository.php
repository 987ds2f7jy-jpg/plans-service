<?php

namespace App\Repositories\UserExternalAccess;

use App\Models\UserExternalAccess;
use App\Repositories\BaseRepository;

class UserExternalAccessRepository extends BaseRepository implements UserExternalAccessRepositoryInterface
{
    public function __construct(UserExternalAccess $model)
    {
        parent::__construct($model);
    }
}
