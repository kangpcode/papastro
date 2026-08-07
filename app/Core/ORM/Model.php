<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.2.0
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
 * PapastroModel — Base Active Record style model (Eloquent-inspired).
 */
abstract class Model
{
    /**
     * The database table name.
     */
    protected string $table = '';

    /**
     * The primary key for the model.
     */
    protected string $primaryKey = 'id';

    /**
     * Indicates if the primary key is auto-incrementing.
     */
    protected bool $incrementing = true;

    /**
     * The model's mass-assignable attributes.
     */
    protected array $fillable = [];

    /**
     * The model's guarded attributes.
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected array $hidden = [];

    /**
     * Indicates if the model should use timestamps.
     */
    protected bool $timestamps = true;

    /**
     * The model's attributes.
     */
    protected array $attributes = [];

    /**
     * The original attribute values.
     */
    protected array $original = [];

    /**
     * Whether the model exists in the database.
     */
    public bool $exists = false;

    /**
     * The database connection resolver (Connection instance or lazy callable).
     */
    protected static mixed $resolver = null;

    /**
     * Resolved Connection instance (cached after first lazy call).
     */
    protected static ?Connection $resolvedConnection = null;

    /**
     * Create a new model instance.
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    /**
     * Set the connection resolver for all models.
     * Accepts a Connection instance or a callable that returns one (lazy).
     */
    public static function setConnectionResolver(Connection|callable $connection): void
    {
        static::$resolver = $connection;
        static::$resolvedConnection = null; // Reset cache on re-registration
    }

    /**
     * Get the database connection, resolving lazily if needed.
     */
    protected static function getConnection(): Connection
    {
        if (static::$resolvedConnection !== null) {
            return static::$resolvedConnection;
        }

        if (static::$resolver === null) {
            throw new \RuntimeException('No database connection resolver has been set.');
        }

        if (is_callable(static::$resolver)) {
            static::$resolvedConnection = (static::$resolver)();
        } else {
            static::$resolvedConnection = static::$resolver;
        }

        return static::$resolvedConnection;
    }

    /**
     * Get a new QueryBuilder for this model's table.
     */
    public static function query(): QueryBuilder
    {
        $instance = new static;
        return static::getConnection()->table($instance->getTable());
    }

    /**
     * Get the table name for this model.
     */
    public function getTable(): string
    {
        if ($this->table) {
            return $this->table;
        }

        // Auto-derive from class name: Post → posts, BlogPost → blog_posts
        $class = basename(str_replace('\\', '/', static::class));
        $snake = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($class)));
        return rtrim($snake, '_') . 's';
    }

    /**
     * Fill the model with an array of attributes.
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Determine if the given attribute is fillable.
     */
    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->guarded)) {
            return false;
        }

        if (empty($this->fillable)) {
            return true;
        }

        return in_array($key, $this->fillable);
    }

    /**
     * Get an attribute value.
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set an attribute value.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get the primary key value.
     */
    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Get all attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get the attributes that have changed since original.
     */
    public function getDirty(): array
    {
        return array_diff_assoc($this->attributes, $this->original);
    }

    /**
     * Determine if the model or given attribute is dirty.
     */
    public function isDirty(?string $attribute = null): bool
    {
        if ($attribute) {
            return isset($this->getDirty()[$attribute]);
        }
        return !empty($this->getDirty());
    }

    /**
     * Create a new model from an array of attributes (skips fillable).
     */
    protected static function newFromBuilder(array $attributes): static
    {
        $instance = new static;
        $instance->attributes = $attributes;
        $instance->original = $attributes;
        $instance->exists = true;
        return $instance;
    }

    /**
     * Convert a raw array row to a model instance.
     */
    protected static function hydrate(array $rows): array
    {
        return array_map(fn($row) => static::newFromBuilder($row), $rows);
    }

    // -----------------------------------------------------------------------
    // Static Query Methods
    // -----------------------------------------------------------------------

    /**
     * Get all records.
     */
    public static function all(): array
    {
        return static::hydrate(static::query()->get());
    }

    /**
     * Find a model by primary key.
     */
    public static function find(mixed $id): ?static
    {
        $instance = new static;
        $row = static::query()->find($id, $instance->primaryKey);
        return $row ? static::newFromBuilder($row) : null;
    }

    /**
     * Find a model or throw an exception.
     */
    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new \RuntimeException(static::class . " with ID [{$id}] not found.");
        }
        return $model;
    }

    /**
     * Start a WHERE query.
     */
    public static function where(string $column, mixed $operatorOrValue, mixed $value = null): ModelQueryBuilder
    {
        return (new ModelQueryBuilder(static::query(), static::class))
            ->where($column, $operatorOrValue, $value);
    }

    /**
     * Get latest records.
     */
    public static function latest(string $column = 'created_at'): ModelQueryBuilder
    {
        return (new ModelQueryBuilder(static::query(), static::class))->latest($column);
    }

    /**
     * Create a new model and save it to the database.
     */
    public static function create(array $attributes): static
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    /**
     * Find or create a model.
     */
    public static function firstOrCreate(array $where, array $data = []): static
    {
        $instance = new static;
        $qb = static::query();
        foreach ($where as $col => $val) {
            $qb->where($col, $val);
        }
        $row = $qb->first();

        if ($row) {
            return static::newFromBuilder($row);
        }

        return static::create(array_merge($where, $data));
    }

    /**
     * Update or create a model.
     */
    public static function updateOrCreate(array $where, array $data = []): static
    {
        $instance = new static;
        $qb = static::query();
        foreach ($where as $col => $val) {
            $qb->where($col, $val);
        }
        $row = $qb->first();

        if ($row) {
            $model = static::newFromBuilder($row);
            $model->fill($data);
            $model->save();
            return $model;
        }

        return static::create(array_merge($where, $data));
    }

    /**
     * Save the model to the database.
     */
    public function save(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!$this->exists) {
                $this->attributes['created_at'] = $now;
            }
            $this->attributes['updated_at'] = $now;
        }

        if ($this->exists) {
            $dirty = $this->getDirty();
            if (!empty($dirty)) {
                static::query()
                    ->where($this->primaryKey, $this->getKey())
                    ->update($dirty);
            }
        } else {
            $id = static::query()->insert($this->attributes);
            $this->attributes[$this->primaryKey] = $id;
            $this->exists = true;
        }

        $this->original = $this->attributes;

        return true;
    }

    /**
     * Update model attributes and save.
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    /**
     * Delete the model from the database.
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        static::query()
            ->where($this->primaryKey, $this->getKey())
            ->delete();

        $this->exists = false;

        return true;
    }

    /**
     * Convert model to array.
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;

        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    /**
     * Convert model to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Magic getter for attributes.
     */
    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    /**
     * Magic setter for attributes.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Magic isset check.
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
}
