<?php

namespace App\Repositories\ExternalAccessService;

use App\Models\ExternalAccessService;
use App\Repositories\BaseRepository;

class ExternalAccessServiceRepository extends BaseRepository implements ExternalAccessServiceRepositoryInterface
{
    public function __construct(ExternalAccessService $model)
    {
        parent::__construct($model);
    }
}
