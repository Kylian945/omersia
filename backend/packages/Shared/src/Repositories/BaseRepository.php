<?php

declare(strict_types=1);

namespace Omersia\Shared\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Omersia\Shared\Contracts\RepositoryInterface;

/** @template TModel of Model */
abstract class BaseRepository implements RepositoryInterface
{
    /** @var TModel */
    protected Model $model;

    /** @var Builder<TModel> */
    protected Builder $query;

    /** @param TModel $model */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    protected function resetQuery(): self
    {
        /** @var Builder<TModel> $query */
        $query = $this->model->newQuery();
        $this->query = $query;

        return $this;
    }

    /**
     * @return Collection<int, TModel>
     */
    public function all(): Collection
    {
        $result = $this->query->get();
        $this->resetQuery();

        return $result;
    }

    /**
     * @return TModel|null
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * @return TModel
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * @return TModel|null
     */
    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }

    /**
     * @return Collection<int, TModel>
     */
    public function findAllBy(string $field, mixed $value): Collection
    {
        /** @var Collection<int, TModel> $result */
        $result = $this->model->where($field, $value)->get();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
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

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = 15)
    {
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<TModel> $result */
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
