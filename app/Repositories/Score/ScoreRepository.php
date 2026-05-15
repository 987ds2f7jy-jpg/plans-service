<?php

namespace App\Repositories\Score;

use App\Models\Score;
use App\Repositories\BaseRepository;

class ScoreRepository extends BaseRepository implements ScoreRepositoryInterface
{
    public function __construct(Score $model)
    {
        parent::__construct($model);
    }
}
