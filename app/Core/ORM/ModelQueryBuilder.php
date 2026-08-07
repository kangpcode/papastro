<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\ORM;

/**
 * ModelQueryBuilder wraps QueryBuilder and hydrates results as Model instances.
 */
class ModelQueryBuilder
{
    protected QueryBuilder $builder;
    protected string $modelClass;

    public function __construct(QueryBuilder $builder, string $modelClass)
    {
        $this->builder    = $builder;
        $this->modelClass = $modelClass;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        $this->builder->where($column, $operatorOrValue, $value);
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->builder->whereIn($column, $values);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->builder->orderBy($column, $direction);
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        $this->builder->latest($column);
        return $this;
    }

    public function oldest(string $column = 'created_at'): static
    {
        $this->builder->oldest($column);
        return $this;
    }

    public function limit(int $value): static
    {
        $this->builder->limit($value);
        return $this;
    }

    public function offset(int $value): static
    {
        $this->builder->offset($value);
        return $this;
    }

    public function get(): array
    {
        $rows = $this->builder->get();
        return array_map(fn ($row) => $this->modelClass::newFromBuilder($row), $rows);
    }

    public function first(): ?Model
    {
        $row = $this->builder->first();
        return $row ? $this->modelClass::newFromBuilder($row) : null;
    }

    public function count(): int
    {
        return $this->builder->count();
    }

    public function exists(): bool
    {
        return $this->builder->exists();
    }

    public function delete(): int
    {
        return $this->builder->delete();
    }

    public function update(array $data): int
    {
        return $this->builder->update($data);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $result = $this->builder->paginate($perPage, $page);
        $modelClass = $this->modelClass;
        $result['data'] = array_map(fn ($row) => $modelClass::newFromBuilder($row), $result['data']);
        return $result;
    }

    public function __call(string $method, array $args): mixed
    {
        $result = $this->builder->$method(...$args);
        if ($result === $this->builder) {
            return $this;
        }
        return $result;
    }
}
