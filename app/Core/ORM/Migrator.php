<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
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
 * Database Migrator — runs and rolls back migration files.
 */
class Migrator
{
    protected Connection $connection;
    protected string $migrationsPath;
    protected string $migrationsTable;

    public function __construct(Connection $connection, string $migrationsPath)
    {
        $this->connection = $connection;
        $this->migrationsPath = $migrationsPath;
        $this->migrationsTable = config('database.migrations.table', 'papastro_migrations');

        $this->ensureMigrationsTableExists();
    }

    /**
     * Create the migrations table if it doesn't exist.
     */
    protected function ensureMigrationsTableExists(): void
    {
        try {
            $this->connection->statement("
                CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
                    `id`        INT AUTO_INCREMENT PRIMARY KEY,
                    `migration` VARCHAR(255) NOT NULL,
                    `batch`     INT NOT NULL,
                    `ran_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } catch (\Exception $e) {
            // SQLite syntax
            $this->connection->statement("
                CREATE TABLE IF NOT EXISTS \"{$this->migrationsTable}\" (
                    \"id\"        INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"migration\" TEXT NOT NULL,
                    \"batch\"     INTEGER NOT NULL,
                    \"ran_at\"    TEXT DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
    }

    /**
     * Get already run migrations.
     */
    protected function getRanMigrations(): array
    {
        $rows = $this->connection->select("SELECT migration FROM `{$this->migrationsTable}`");
        return array_column($rows, 'migration');
    }

    /**
     * Get the current max batch number.
     */
    protected function getLastBatch(): int
    {
        $row = $this->connection->selectOne("SELECT MAX(batch) as batch FROM `{$this->migrationsTable}`");
        return (int) ($row['batch'] ?? 0);
    }

    /**
     * Run all pending migrations.
     */
    public function run(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $ran = $this->getRanMigrations();
        $batch = $this->getLastBatch() + 1;
        $files = glob($this->migrationsPath . '/*.php');
        sort($files);

        $executed = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran))
                continue;

            $migration = require $file;
            $migration->setConnection($this->connection);
            $migration->up();

            $this->connection->statement(
                "INSERT INTO `{$this->migrationsTable}` (migration, batch) VALUES (?, ?)",
                [$name, $batch]
            );

            $executed[] = $name;
        }

        return $executed;
    }

    /**
     * Rollback the last batch of migrations.
     */
    public function rollback(): array
    {
        $batch = $this->getLastBatch();
        if ($batch === 0)
            return [];

        $rows = $this->connection->select(
            "SELECT migration FROM `{$this->migrationsTable}` WHERE batch = ? ORDER BY id DESC",
            [$batch]
        );
        $names = array_column($rows, 'migration');

        $rolledBack = [];
        foreach ($names as $name) {
            $file = $this->migrationsPath . "/{$name}.php";
            if (!file_exists($file))
                continue;

            $migration = require $file;
            $migration->setConnection($this->connection);
            $migration->down();

            $this->connection->statement(
                "DELETE FROM `{$this->migrationsTable}` WHERE migration = ?",
                [$name]
            );

            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    /**
     * Get the status of all migrations (both ran and pending).
     *
     * @return array<int, array{ran: bool, migration: string, batch: int|null, ran_at: string|null}>
     */
    public function getStatus(): array
    {
        $this->ensureMigrationsTableExists();

        $rows = $this->connection->select("SELECT migration, batch, ran_at FROM `{$this->migrationsTable}` ORDER BY id ASC");
        $ranMap = [];
        foreach ($rows as $row) {
            $ranMap[$row['migration']] = $row;
        }

        $files = is_dir($this->migrationsPath) ? glob($this->migrationsPath . '/*.php') : [];
        sort($files);

        $statusList = [];
        $seen = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $seen[$name] = true;
            $statusList[] = [
                'ran'       => isset($ranMap[$name]),
                'migration' => $name,
                'batch'     => $ranMap[$name]['batch'] ?? null,
                'ran_at'    => $ranMap[$name]['ran_at'] ?? null,
            ];
        }

        // Include any migration recorded in DB but missing from disk
        foreach ($ranMap as $name => $meta) {
            if (!isset($seen[$name])) {
                $statusList[] = [
                    'ran'       => true,
                    'migration' => $name . ' (missing file)',
                    'batch'     => $meta['batch'] ?? null,
                    'ran_at'    => $meta['ran_at'] ?? null,
                ];
            }
        }

        return $statusList;
    }

    /**
     * Rollback all database migrations.
     */
    public function reset(): array
    {
        $rolledBack = [];
        while ($this->getLastBatch() > 0) {
            $batchRolled = $this->rollback();
            if (empty($batchRolled)) {
                break;
            }
            $rolledBack = array_merge($rolledBack, $batchRolled);
        }

        return $rolledBack;
    }

    /**
     * Drop all tables from the database.
     */
    public function dropAllTables(): void
    {
        $driver = $this->connection->getDriverName();

        if ($driver === 'sqlite') {
            $tables = $this->connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
            foreach ($tables as $t) {
                $tableName = $t['name'] ?? null;
                if ($tableName) {
                    $this->connection->statement("DROP TABLE IF EXISTS \"{$tableName}\"");
                }
            }
        } elseif ($driver === 'mysql') {
            $this->connection->statement('SET FOREIGN_KEY_CHECKS = 0');
            $tables = $this->connection->select('SHOW TABLES');
            foreach ($tables as $row) {
                $tableName = reset($row);
                if ($tableName) {
                    $this->connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
                }
            }
            $this->connection->statement('SET FOREIGN_KEY_CHECKS = 1');
        } elseif ($driver === 'pgsql') {
            $this->connection->statement('DROP SCHEMA public CASCADE');
            $this->connection->statement('CREATE SCHEMA public');
        }

        $this->ensureMigrationsTableExists();
    }

    /**
     * Drop all tables and re-run all migrations from scratch.
     */
    public function fresh(): array
    {
        $this->dropAllTables();
        return $this->run();
    }
}

/**
 * Base Migration class — implements table builder shortcuts.
 */
abstract class Migration
{
    protected Connection $connection;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    abstract public function up(): void;
    abstract public function down(): void;

    /**
     * Create a table with a builder closure.
     */
    protected function create(string $table, callable $callback): void
    {
        $builder = new SchemaBuilder($table, false, $this->connection->getDriverName());
        $callback($builder);
        $this->connection->statement($builder->toCreateSql());
    }

    /**
     * Drop a table if it exists.
     */
    protected function dropIfExists(string $table): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS `{$table}`");
    }

    /**
     * Add columns to an existing table.
     */
    protected function table(string $table, callable $callback): void
    {
        $builder = new SchemaBuilder($table, true, $this->connection->getDriverName());
        $callback($builder);
        foreach ($builder->toAlterSql() as $sql) {
            $this->connection->statement($sql);
        }
    }
}

/**
 * SchemaBuilder — fluent table schema builder for migrations.
 */
class SchemaBuilder
{
    protected string $table;
    protected bool $alter;
    protected string $driver;
    protected array $columns = [];
    protected array $indexes = [];

    public function __construct(string $table, bool $alter = false, string $driver = 'mysql')
    {
        $this->table = $table;
        $this->alter = $alter;
        $this->driver = $driver;
    }

    public function id(string $name = 'id'): static
    {
        if ($this->driver === 'sqlite') {
            $this->columns[] = "`{$name}` INTEGER PRIMARY KEY AUTOINCREMENT";
        } elseif ($this->driver === 'pgsql') {
            $this->columns[] = "`{$name}` SERIAL PRIMARY KEY";
        } else {
            $this->columns[] = "`{$name}` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        }
        return $this;
    }

    public function string(string $name, int $length = 255): static
    {
        $this->columns[] = "`{$name}` VARCHAR({$length}) NOT NULL DEFAULT ''";
        return $this;
    }

    public function text(string $name): static
    {
        $this->columns[] = "`{$name}` TEXT";
        return $this;
    }

    public function longText(string $name): static
    {
        $type = $this->driver === 'sqlite' ? 'TEXT' : 'LONGTEXT';
        $this->columns[] = "`{$name}` {$type}";
        return $this;
    }

    public function integer(string $name): static
    {
        $this->columns[] = "`{$name}` INT NOT NULL DEFAULT 0";
        return $this;
    }

    public function unsignedBigInteger(string $name): static
    {
        $type = $this->driver === 'sqlite' ? 'INTEGER' : 'BIGINT UNSIGNED';
        $this->columns[] = "`{$name}` {$type} NOT NULL DEFAULT 0";
        return $this;
    }

    public function boolean(string $name, bool $default = false): static
    {
        $def = $default ? 1 : 0;
        $type = $this->driver === 'sqlite' ? 'INTEGER' : 'TINYINT(1)';
        $this->columns[] = "`{$name}` {$type} NOT NULL DEFAULT {$def}";
        return $this;
    }

    public function timestamp(string $name): static
    {
        $this->columns[] = "`{$name}` TIMESTAMP NULL";
        return $this;
    }

    public function timestamps(): static
    {
        $this->columns[] = "`created_at` TIMESTAMP NULL DEFAULT NULL";
        $this->columns[] = "`updated_at` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }

    public function softDeletes(): static
    {
        $this->columns[] = "`deleted_at` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }

    public function nullable(): static
    {
        $last = &$this->columns[count($this->columns) - 1];
        $last = str_replace('NOT NULL', 'NULL', $last);
        return $this;
    }

    public function unique(string ...$columns): static
    {
        $cols = implode('`, `', $columns);
        if ($this->driver === 'sqlite') {
            $this->indexes[] = "UNIQUE (`{$cols}`)";
        } else {
            $this->indexes[] = "UNIQUE KEY `uk_" . implode('_', $columns) . "` (`{$cols}`)";
        }
        return $this;
    }

    public function index(string ...$columns): static
    {
        $cols = implode('`, `', $columns);
        if ($this->driver === 'sqlite') {
            // SQLite ignores inline KEY in CREATE TABLE, create separate index if needed
        } else {
            $this->indexes[] = "KEY `idx_" . implode('_', $columns) . "` (`{$cols}`)";
        }
        return $this;
    }

    public function toCreateSql(): string
    {
        $defs = array_merge($this->columns, $this->indexes);
        $cols = implode(",\n    ", $defs);

        if ($this->driver === 'sqlite') {
            return "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n    {$cols}\n)";
        }

        return "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n    {$cols}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    public function toAlterSql(): array
    {
        $sqls = [];
        foreach ($this->columns as $col) {
            $sqls[] = "ALTER TABLE `{$this->table}` ADD COLUMN {$col}";
        }
        foreach ($this->indexes as $idx) {
            $sqls[] = "ALTER TABLE `{$this->table}` ADD {$idx}";
        }
        return $sqls;
    }
}
