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

use PDO;
use PDOException;
use Papastro\Debugger\QueryCollector;

/**
 * Database Connection wrapper around PDO.
 */
class Connection
{
    protected ?PDO $pdo = null;
    protected ?PDO $readPdo = null;
    protected array $config;
    protected ?QueryCollector $collector = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the PDO instance (lazy connection for writes and transactions).
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $writeConfig = $this->config;
            if (!empty($this->config['write']) && is_array($this->config['write'])) {
                $writeConfig = array_merge($this->config, $this->config['write']);
            }
            $this->pdo = $this->createPdoFromConfig($writeConfig);
        }
        return $this->pdo;
    }

    /**
     * Get the PDO instance for read queries (supports read replicas).
     */
    public function getReadPdo(): PDO
    {
        if ($this->inTransaction()) {
            return $this->getPdo();
        }

        if ($this->readPdo !== null) {
            return $this->readPdo;
        }

        if (!empty($this->config['read']) && is_array($this->config['read'])) {
            $readConfig = $this->config['read'];
            // Load balance randomly if multiple read replica hosts provided
            if (isset($readConfig[0]) && is_array($readConfig[0])) {
                $readConfig = $readConfig[array_rand($readConfig)];
            }
            $merged = array_merge($this->config, $readConfig);
            return $this->readPdo = $this->createPdoFromConfig($merged);
        }

        return $this->getPdo();
    }

    /**
     * Create a new PDO instance from specific connection config.
     */
    protected function createPdoFromConfig(array $cfg): PDO
    {
        $driver = $cfg['driver'] ?? 'mysql';
        $host = $cfg['host'] ?? 'localhost';
        $port = $cfg['port'] ?? '3306';
        $dbname = $cfg['database'] ?? '';
        $charset = $cfg['charset'] ?? 'utf8mb4';
        $username = $cfg['username'] ?? 'root';
        $password = $cfg['password'] ?? '';

        switch ($driver) {
            case 'sqlite':
                if ($dbname !== ':memory:' && !str_starts_with($dbname, '/') && !preg_match('/^[a-zA-Z]:\\\\/', $dbname)) {
                    $dbname = base_path($dbname);
                }
                if ($dbname !== ':memory:') {
                    $dir = dirname($dbname);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    if (!file_exists($dbname)) {
                        @touch($dbname);
                    }
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
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }

        if ($driver === 'sqlite') {
            // WAL + busy timeout make SQLite safe for many concurrent runtime workers.
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            $pdo->exec('PRAGMA synchronous = NORMAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        return $pdo;
    }

    /**
     * Create a new PDO instance from default config.
     */
    protected function createPdo(): PDO
    {
        return $this->createPdoFromConfig($this->config);
    }

    /**
     * Determine if the PDO exception means the connection was lost.
     */
    protected function isConnectionLost(PDOException $e): bool
    {
        $msg = strtolower($e->getMessage());
        foreach (['server has gone away', 'lost connection', 'no connection to the server', 'connection reset', 'broken pipe', 'is dead or not enabled'] as $needle) {
            if (str_contains($msg, $needle)) {
                return true;
            }
        }
        return false;
    }

    public function inTransaction(): bool
    {
        return $this->pdo !== null && $this->pdo->inTransaction();
    }

    /**
     * Drop the PDO handles so the next query reconnects.
     */
    public function disconnect(): void
    {
        $this->pdo = null;
        $this->readPdo = null;
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

        try {
            $stmt = $this->getPdo()->prepare($sql);
            $stmt->execute($bindings);
        } catch (PDOException $e) {
            // Long-running workers: transparently reconnect once if the server dropped us.
            if (!$this->inTransaction() && $this->isConnectionLost($e)) {
                $this->disconnect();
                $stmt = $this->getPdo()->prepare($sql);
                $stmt->execute($bindings);
            } else {
                throw $e;
            }
        }

        $elapsed = microtime(true) - $start;

        $this->collector?->record($sql, $bindings, $elapsed);

        return $stmt;
    }

    /**
     * Execute a read query across read replicas and return a PDOStatement.
     */
    public function queryRead(string $sql, array $bindings = []): \PDOStatement
    {
        $start = microtime(true);

        try {
            $stmt = $this->getReadPdo()->prepare($sql);
            $stmt->execute($bindings);
        } catch (PDOException $e) {
            if (!$this->inTransaction() && $this->isConnectionLost($e)) {
                $this->readPdo = null;
                $stmt = $this->getReadPdo()->prepare($sql);
                $stmt->execute($bindings);
            } else {
                throw $e;
            }
        }

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
        return $this->queryRead($sql, $bindings)->fetchAll();
    }

    /**
     * Run a SELECT and return first row.
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $result = $this->queryRead($sql, $bindings)->fetch();
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
        // Nested call → SAVEPOINT so an inner failure only rolls back the inner block.
        if ($this->inTransaction()) {
            $name = 'sp_' . bin2hex(random_bytes(4));
            $this->getPdo()->exec("SAVEPOINT {$name}");
            try {
                $result = $callback($this);
                $this->getPdo()->exec("RELEASE SAVEPOINT {$name}");
                return $result;
            } catch (\Throwable $e) {
                $this->getPdo()->exec("ROLLBACK TO SAVEPOINT {$name}");
                throw $e;
            }
        }

        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }
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
