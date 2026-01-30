<?php

declare(strict_types=1);

namespace Omersia\Shared\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Omersia\Shared\Contracts\RepositoryInterface;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    protected Builder $query;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    protected function resetQuery(): self
    {
        $this->query = $this->model->newQuery();

        return $this;
    }

    public function all(): Collection
    {
        $result = $this->query->get();
        $this->resetQuery();

        return $result;
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }

    public function findAllBy(string $field, mixed $value): Collection
    {
        return $this->model->where($field, $value)->get();
    }

    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    public function update(int $id, array $attributes): bool
    {
        $model = $this->findOrFail($id);

        return $model->update($attributes);
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    public function paginate(int $perPage = 15)
    {
        $result = $this->query->paginate($perPage);
        $this->resetQuery();

        return $result;
    }

    public function with(array $relations): self
    {
        $this->query->with($relations);

        return $this;
    }

    public function where(string $field, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $this->query->where($field, $operator);
        } else {
            $this->query->where($field, $operator, $value);
        }

        return $this;
    }
}
