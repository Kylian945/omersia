<?php

declare(strict_types=1);

namespace Omersia\Shared\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    public function find(int $id): ?Model;

    public function findOrFail(int $id): Model;

    public function findBy(string $field, mixed $value): ?Model;

    /**
     * @return Collection<int, Model>
     */
    public function findAllBy(string $field, mixed $value): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    public function update(int $id, array $attributes): bool;

    public function delete(int $id): bool;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<Model>
     */
    public function paginate(int $perPage = 15);

    public function with(array $relations): self;

    public function where(string $field, mixed $operator, mixed $value = null): self;
}
