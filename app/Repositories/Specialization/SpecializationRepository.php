<?php

namespace App\Repositories\Specialization;

use App\Models\Specialization;
use App\Repositories\BaseRepository;

class SpecializationRepository extends BaseRepository implements SpecializationRepositoryInterface
{
    public function __construct(Specialization $model)
    {
        parent::__construct($model);
    }
}
