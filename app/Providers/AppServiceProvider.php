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

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register app-level bindings here.
        // Example: $this->app->singleton(MyInterface::class, MyImplementation::class);
    }

    public function boot(): void
    {
        // Boot app-level services here.
        // Example: view composers, event listeners, macros.
    }
}
