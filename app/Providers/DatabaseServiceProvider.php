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

namespace App\Providers;

use Papastro\Foundation\ServiceProvider;
use Papastro\ORM\Connection;
use Papastro\ORM\DatabaseManager;
use Papastro\ORM\Model;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 1. Register DatabaseManager singleton for multi-database connection management
        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager($app);
        });

        // 2. Default Connection instance resolves through DatabaseManager
        $this->app->singleton(Connection::class, function ($app) {
            $manager = $app->make(DatabaseManager::class);
            $conn = $manager->connection();
            if ($conn instanceof Connection) {
                return $conn;
            }
            $config = $app->make('config');
            $driver = $config->get('database.default', 'sqlite');
            $dbConf = $config->get("database.connections.{$driver}", []);
            return new Connection($dbConf);
        });

        $this->app->alias(DatabaseManager::class, 'db');
        $this->app->alias(DatabaseManager::class, 'db.manager');
    }

    public function boot(): void
    {
        // Set a lazy resolver on the Model class so it only connects when a query is made.
        // This prevents DB connection errors when just running CLI commands without DB.
        Model::setConnectionResolver(function () {
            return $this->app->make(Connection::class);
        });
    }
}
