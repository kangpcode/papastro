<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0
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
 * MongoConnection — MongoDB document database connection for Papastro.
 * Supports MongoDB Atlas, local MongoDB, replica sets, and collection operations.
 */
class MongoConnection
{
    protected array $config;
    protected mixed $client = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get or create the underlying MongoDB\Client instance.
     */
    public function getClient(): mixed
    {
        if ($this->client === null) {
            if (!class_exists('\MongoDB\Client')) {
                throw new \RuntimeException(
                    "MongoDB driver not found. To use MongoDB in Papastro, please install the MongoDB PHP extension (ext-mongodb) and library: 'composer require mongodb/mongodb'."
                );
            }

            $uri = $this->config['dsn'] ?? $this->config['url'] ?? 'mongodb://127.0.0.1:27017';
            $uriOptions = $this->config['options'] ?? [];
            $driverOptions = $this->config['driver_options'] ?? [];

            $this->client = new \MongoDB\Client($uri, $uriOptions, $driverOptions);
        }

        return $this->client;
    }

    /**
     * Get a MongoDB Database instance.
     */
    public function getDatabase(?string $name = null): mixed
    {
        $client = $this->getClient();
        $dbName = $name ?: ($this->config['database'] ?? 'papastro');

        return $client->selectDatabase($dbName);
    }

    /**
     * Get a MongoDB Collection instance for query and document operations.
     */
    public function collection(string $name): mixed
    {
        return $this->getDatabase()->selectCollection($name);
    }

    /**
     * Alias for collection().
     */
    public function table(string $name): mixed
    {
        return $this->collection($name);
    }

    /**
     * Get the database driver name.
     */
    public function getDriverName(): string
    {
        return 'mongodb';
    }

    /**
     * Disconnect client handle.
     */
    public function disconnect(): void
    {
        $this->client = null;
    }
}

