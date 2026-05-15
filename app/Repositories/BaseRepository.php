<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function createMany(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $collection = new Collection();

            foreach ($data as $item) {
                $collection->push($this->model->create($item));
            }

            return $collection;
        });
    }

    public function findBy(
        array $filters,
        array $columns = ['*'],
        array $relations = [],
        ?string $orderBy = null,
        string $direction = 'asc'
    ): Collection {
        $query = $this->model->query();

        foreach ($filters as $key => $value) {
            if (is_array($value) && count($value) === 3) {
                $query->where($value[0], $value[1], $value[2]);
            } else {
                $query->where($key, $value);
            }
        }

        if (!empty($relations)) {
            $query->with($relations);
        }

        if ($orderBy) {
            $query->orderBy($orderBy, $direction);
        }

        return $query->get($columns);
    }

    public function findOneBy(
        array $filters,
        array $columns = ['*'],
        array $relations = [],
        ?string $orderBy = null,
        string $direction = 'asc'
    ): ?Model {
        return $this->findBy($filters, $columns, $relations, $orderBy, $direction)->first();
    }

    public function findById(string|int $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    public function findAndLock(string|int $id, array $columns = ['*']): ?Model
    {
        return $this->model->lockForUpdate()->find($id, $columns);
    }

    public function findAll(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }

    public function update(string|int $id, array $data): ?Model
    {
        $this->model->where('id', $id)->update($data);

        return $this->model->find($id);
    }
}
