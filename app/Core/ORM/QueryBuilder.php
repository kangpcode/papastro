<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\ORM;

use Papastro\Security\Security;

/**
 * Fluent QueryBuilder — Eloquent-inspired SQL query builder.
 */
class QueryBuilder
{
    protected Connection $connection;
    protected string $table;
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $columns = ['*'];
    protected array $orders = [];
    protected array $joins = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;
    protected ?string $groupBy = null;
    protected array $havings = [];

    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Set the columns to select.
     */
    public function select(string|array $columns = ['*']): static
    {
        $cols = is_array($columns) ? $columns : func_get_args();
        $this->columns = array_map([Security::class, 'sanitizeSqlIdentifier'], $cols);
        return $this;
    }

    /**
     * Add a WHERE clause.
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        $column = Security::sanitizeSqlIdentifier($column);
        if (func_num_args() === 2) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $this->validOperator((string) $operatorOrValue);
        }

        if ($value === null) {
            $this->wheres[] = $operator === '!=' || $operator === '<>' ? "{$column} IS NOT NULL" : "{$column} IS NULL";
            return $this;
        }

        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Whitelist SQL comparison operators (user input must never reach the query string).
     */
    protected function validOperator(string $operator): string
    {
        $op = strtoupper(trim($operator));
        $allowed = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];
        if (!in_array($op, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid SQL operator [{$operator}].");
        }
        return $op;
    }

    /**
     * Add a WHERE IN clause.
     */
    public function whereIn(string $column, array $values): static
    {
        $column = Security::sanitizeSqlIdentifier($column);
        if (empty($values)) {
            $this->wheres[] = '0 = 1';
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add a WHERE NOT IN clause.
     */
    public function whereNotIn(string $column, array $values): static
    {
        $column = Security::sanitizeSqlIdentifier($column);
        if (empty($values)) {
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} NOT IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add a WHERE NULL clause.
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = Security::sanitizeSqlIdentifier($column) . ' IS NULL';
        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause.
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = Security::sanitizeSqlIdentifier($column) . ' IS NOT NULL';
        return $this;
    }

    /**
     * Add a WHERE LIKE clause.
     */
    public function whereLike(string $column, string $value): static
    {
        return $this->where($column, 'LIKE', $value);
    }

    /**
     * Add a WHERE BETWEEN clause.
     */
    public function whereBetween(string $column, mixed $min, mixed $max): static
    {
        $this->wheres[] = Security::sanitizeSqlIdentifier($column) . ' BETWEEN ? AND ?';
        $this->bindings[] = $min;
        $this->bindings[] = $max;
        return $this;
    }

    /**
     * Add an OR WHERE clause.
     */
    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        $column = Security::sanitizeSqlIdentifier($column);
        if (func_num_args() === 2) {
            $value = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $this->validOperator((string) $operatorOrValue);
        }

        $clause = $value === null ? "{$column} IS NULL" : "{$column} {$operator} ?";

        $lastIndex = count($this->wheres) - 1;
        if ($lastIndex >= 0) {
            $this->wheres[$lastIndex] = '(' . $this->wheres[$lastIndex] . " OR {$clause})";
        } else {
            $this->wheres[] = $clause;
        }
        if ($value !== null) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    /**
     * Add an ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = Security::sanitizeSqlIdentifier($column) . " {$direction}";
        return $this;
    }

    /**
     * Shorthand for orderBy descending.
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Shorthand for orderBy ascending.
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Set the LIMIT value.
     */
    public function limit(int $value): static
    {
        $this->limitValue = $value;
        return $this;
    }

    /**
     * Alias for limit.
     */
    public function take(int $value): static
    {
        return $this->limit($value);
    }

    /**
     * Set the OFFSET value.
     */
    public function offset(int $value): static
    {
        $this->offsetValue = $value;
        return $this;
    }

    /**
     * Alias for offset.
     */
    public function skip(int $value): static
    {
        return $this->offset($value);
    }

    /**
     * Add a GROUP BY clause.
     */
    public function groupBy(string $column): static
    {
        $this->groupBy = Security::sanitizeSqlIdentifier($column);
        return $this;
    }

    /**
     * Add a JOIN clause.
     */
    public function join(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = 'INNER JOIN ' . Security::sanitizeSqlIdentifier($table) . ' ON ' . Security::sanitizeSqlIdentifier($first) . ' ' . $this->validOperator($operator) . ' ' . Security::sanitizeSqlIdentifier($second);
        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->joins[] = 'LEFT JOIN ' . Security::sanitizeSqlIdentifier($table) . ' ON ' . Security::sanitizeSqlIdentifier($first) . ' ' . $this->validOperator($operator) . ' ' . Security::sanitizeSqlIdentifier($second);
        return $this;
    }

    /**
     * Build the SELECT SQL statement.
     */
    public function toSql(): string
    {
        $cols = implode(', ', $this->columns);
        $sql = "SELECT {$cols} FROM `{$this->table}`";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        if ($this->groupBy) {
            $sql .= " GROUP BY {$this->groupBy}";
        }

        if (!empty($this->havings)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->havings);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        return $sql;
    }

    /**
     * Execute the query and return all results.
     */
    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->bindings);
    }

    /**
     * Execute the query and return the first result.
     */
    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limitValue = 1;
        return $clone->connection->selectOne($clone->toSql(), $clone->bindings);
    }

    /**
     * Find a record by its primary key.
     */
    public function find(mixed $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, $id)->first();
    }

    /**
     * Run an aggregate on a clone so LIMIT/OFFSET/ORDER of this builder are never mutated.
     */
    protected function aggregate(string $expression): mixed
    {
        $clone = clone $this;
        $clone->columns = ["{$expression} as aggregate"];
        $clone->orders = [];
        $clone->limitValue = null;
        $clone->offsetValue = null;
        $row = $clone->connection->selectOne($clone->toSql(), $clone->bindings);
        return $row['aggregate'] ?? null;
    }

    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT(' . Security::sanitizeSqlIdentifier($column) . ')');
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX(' . Security::sanitizeSqlIdentifier($column) . ')');
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN(' . Security::sanitizeSqlIdentifier($column) . ')');
    }

    public function sum(string $column): mixed
    {
        return $this->aggregate('SUM(' . Security::sanitizeSqlIdentifier($column) . ')') ?? 0;
    }

    public function avg(string $column): mixed
    {
        return $this->aggregate('AVG(' . Security::sanitizeSqlIdentifier($column) . ')');
    }

    /**
     * Get all values for a single column.
     */
    public function pluck(string $column): array
    {
        $col = Security::sanitizeSqlIdentifier($column);
        $clone = clone $this;
        $clone->columns = [$col];
        $key = str_contains($col, '.') ? substr(strrchr($col, '.'), 1) : $col;
        return array_column($clone->get(), $key);
    }

    /**
     * Check if any record matches.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert a record and return the inserted ID.
     */
    public function insert(array $data): string|false
    {
        $keys = array_keys($data);
        $cols = implode(', ', array_map(fn($k) => '`' . Security::sanitizeSqlIdentifier((string) $k) . '`', $keys));
        $phs = implode(', ', array_fill(0, count($keys), '?'));
        $sql = "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$phs})";

        $this->connection->statement($sql, array_values($data));

        return $this->connection->lastInsertId();
    }

    /**
     * Insert multiple records.
     */
    public function insertMany(array $rows): void
    {
        if (empty($rows))
            return;

        $keys = array_keys($rows[0]);
        $cols = implode(', ', array_map(fn($k) => '`' . Security::sanitizeSqlIdentifier((string) $k) . '`', $keys));
        $phs = implode(', ', array_fill(0, count($keys), '?'));
        $sql = "INSERT INTO `{$this->table}` ({$cols}) VALUES ({$phs})";

        foreach ($rows as $row) {
            $this->connection->statement($sql, array_values($row));
        }
    }

    /**
     * Update records matching the current WHERE clause.
     */
    public function update(array $data): int
    {
        $sets = implode(', ', array_map(fn($k) => '`' . Security::sanitizeSqlIdentifier((string) $k) . '` = ?', array_keys($data)));
        $bindings = array_merge(array_values($data), $this->bindings);
        $sql = "UPDATE `{$this->table}` SET {$sets}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        return $this->connection->statement($sql, $bindings);
    }

    /**
     * Update or insert (upsert).
     */
    public function updateOrInsert(array $where, array $data): void
    {
        $builder = clone $this;
        foreach ($where as $col => $val) {
            $builder->where($col, $val);
        }

        if ($builder->exists()) {
            $builder->update($data);
        } else {
            $this->insert(array_merge($where, $data));
        }
    }

    /**
     * Delete records matching the current WHERE clause.
     */
    public function delete(): int
    {
        $sql = "DELETE FROM `{$this->table}`";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        return $this->connection->statement($sql, $this->bindings);
    }

    /**
     * Paginate results.
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $perPage = max(1, min($perPage, 500));
        $page = max(1, $page);
        $total = $this->count();

        $clone = clone $this;
        $results = $clone->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'from' => $total === 0 ? 0 : ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    /**
     * Clone the query builder.
     */
    public function __clone()
    {
        // Ensure arrays are copied, not referenced
    }
}
