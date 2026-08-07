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
use Papastro\Module\ModuleLoader;
use Papastro\Module\ModuleManager;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleLoader::class, fn ($app) => new ModuleLoader($app));

        $this->app->singleton(ModuleManager::class, function ($app) {
            return new ModuleManager(
                $app->make(ModuleLoader::class),
                $app->appPath('Modules')
            );
        });
    }

    public function boot(): void
    {
        $this->app->make(ModuleLoader::class)->load();
    }
}
