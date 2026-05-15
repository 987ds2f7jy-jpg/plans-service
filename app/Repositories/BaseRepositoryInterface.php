<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function create(array $data): Model;

    public function createMany(array $data): Collection;

    public function findBy(
        array $filters,
        array $columns = ['*'],
        array $relations = [],
        ?string $orderBy = null,
        string $direction = 'asc'
    ): Collection;

    public function findOneBy(
        array $filters,
        array $columns = ['*'],
        array $relations = [],
        ?string $orderBy = null,
        string $direction = 'asc'
    ): ?Model;

    public function findById(string|int $id, array $columns = ['*']): ?Model;

    public function findAndLock(string|int $id, array $columns = ['*']): ?Model;

    public function findAll(array $columns = ['*']): Collection;

    public function update(string|int $id, array $data): ?Model;
}
