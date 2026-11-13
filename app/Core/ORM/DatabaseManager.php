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

use Papastro\Foundation\Application;

/**
 * DatabaseManager — Enterprise Multi-Database Connection Manager.
 * Seamlessly manages connections to MySQL, MariaDB, PostgreSQL, Supabase,
 * SQLite, MongoDB, and SQL Server with connection pooling and lazy evaluation.
 */
class DatabaseManager
{
    protected Application $app;

    /**
     * Active connection instances [name => Connection|MongoConnection].
     */
    protected array $connections = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a database connection instance by name (or default connection).
     */
    public function connection(?string $name = null): Connection|MongoConnection
    {
        $name = $name ?: $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = $this->getConnectionConfig($name);
        $driver = $config['driver'] ?? 'mysql';

        if ($driver === 'mongodb') {
            $connection = new MongoConnection($config);
        } else {
            $connection = new Connection($config);

            if ($this->app->debug() && $this->app->bound(\Papastro\Debugger\QueryCollector::class)) {
                $connection->setQueryCollector($this->app->make(\Papastro\Debugger\QueryCollector::class));
            }
        }

        return $this->connections[$name] = $connection;
    }

    /**
     * Get the default connection name.
     */
    public function getDefaultConnection(): string
    {
        return (string) $this->app->make('config')->get('database.default', 'sqlite');
    }

    /**
     * Get configuration array for a connection name.
     */
    public function getConnectionConfig(string $name): array
    {
        $connections = (array) $this->app->make('config')->get('database.connections', []);

        if (!isset($connections[$name])) {
            throw new \InvalidArgumentException("Database connection [{$name}] is not configured in config/database.php.");
        }

        return $connections[$name];
    }

    /**
     * Disconnect a specific connection or all connections.
     */
    public function disconnect(?string $name = null): void
    {
        if ($name !== null) {
            if (isset($this->connections[$name])) {
                $this->connections[$name]->disconnect();
                unset($this->connections[$name]);
            }
            return;
        }

        foreach ($this->connections as $conn) {
            $conn->disconnect();
        }
        $this->connections = [];
    }

    /**
     * Dynamically forward calls to the default connection.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->$method(...$parameters);
    }
}
