<?php

declare(strict_types=1);

namespace Omersia\Shared\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @template TModel of Model */
interface RepositoryInterface
{
    /**
     * @return Collection<int, TModel>
     */
    public function all(): Collection;

    /**
     * @return TModel|null
     */
    public function find(int $id): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(int $id): Model;

    /**
     * @return TModel|null
     */
    public function findBy(string $field, mixed $value): ?Model;

    /**
     * @return Collection<int, TModel>
     */
    public function findAllBy(string $field, mixed $value): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    public function update(int $id, array $attributes): bool;

    public function delete(int $id): bool;

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15);

    public function with(array $relations): self;

    public function where(string $field, mixed $operator, mixed $value = null): self;
}
