<?php

namespace App\RepositoriesUserExternalAccess;

use App\ModelsUserExternalAccess;
use App\Repositories\BaseRepository;

class Repository extends BaseRepository implements RepositoryInterface
{
    public function __construct( )
    {
        parent::__construct();
    }
}
