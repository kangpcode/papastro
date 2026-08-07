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

use PDO;
use PDOException;
use Papastro\Debugger\QueryCollector;

/**
 * Database Connection wrapper around PDO.
 */
class Connection
{
    protected ?PDO $pdo = null;
    protected array $config;
    protected ?QueryCollector $collector = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the PDO instance (lazy connection).
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createPdo();
        }
        return $this->pdo;
    }

    /**
     * Create a new PDO instance from config.
     */
    protected function createPdo(): PDO
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? '3306';
        $dbname = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        $username = $this->config['username'] ?? 'root';
        $password = $this->config['password'] ?? '';

        switch ($driver) {
            case 'sqlite':
                if ($dbname !== ':memory:' && !str_starts_with($dbname, '/') && !preg_match('/^[a-zA-Z]:\\\\/', $dbname)) {
                    $dbname = base_path($dbname);
                }
                $dsn = "sqlite:{$dbname}";
                break;
            case 'pgsql':
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                break;
            default: // mysql
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Set the query collector for debug tracing.
     */
    public function setQueryCollector(QueryCollector $collector): void
    {
        $this->collector = $collector;
    }

    /**
     * Execute a raw query and return a PDOStatement.
     */
    public function query(string $sql, array $bindings = []): \PDOStatement
    {
        $start = microtime(true);

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($bindings);

        $elapsed = microtime(true) - $start;

        $this->collector?->record($sql, $bindings, $elapsed);

        return $stmt;
    }

    /**
     * Execute a statement (INSERT, UPDATE, DELETE) and return rows affected.
     */
    public function statement(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    /**
     * Run a SELECT and return all rows.
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    /**
     * Run a SELECT and return first row.
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $result = $this->query($sql, $bindings)->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Get the ID of the last inserted row.
     */
    public function lastInsertId(): string|false
    {
        return $this->getPdo()->lastInsertId();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->getPdo()->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): bool
    {
        return $this->getPdo()->commit();
    }

    /**
     * Rollback the current transaction.
     */
    public function rollBack(): bool
    {
        return $this->getPdo()->rollBack();
    }

    /**
     * Execute a callback within a transaction.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Get a new QueryBuilder for a table.
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /**
     * Get the database driver name.
     */
    public function getDriverName(): string
    {
        return $this->config['driver'] ?? 'mysql';
    }
}
