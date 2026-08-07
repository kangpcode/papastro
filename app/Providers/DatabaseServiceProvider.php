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

namespace App\Providers;

use Papastro\Foundation\ServiceProvider;
use Papastro\ORM\Connection;
use Papastro\ORM\Model;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Connection::class, function ($app) {
            $config = $app->make('config');
            $driver = $config->get('database.default', 'mysql');
            $dbConf = $config->get("database.connections.{$driver}", []);

            return new Connection($dbConf);
        });

        $this->app->alias(Connection::class, 'db');
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
