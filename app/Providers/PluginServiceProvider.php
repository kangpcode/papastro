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
use Papastro\Plugin\PluginLoader;
use Papastro\Plugin\PluginManager;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app->make('config');
        if (!($config->get('app.plugins.enabled', true))) {
            return;
        }

        $this->app->singleton(PluginLoader::class, fn ($app) => new PluginLoader($app));

        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                $app->make(PluginLoader::class),
                $app->basePath('plugins')
            );
        });
    }

    public function boot(): void
    {
        $config = $this->app->make('config');
        if (!($config->get('app.plugins.enabled', true))) {
            return;
        }

        $this->app->make(PluginLoader::class)->load();
    }
}
