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

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/web.php'));

        if (file_exists(base_path('routes/api.php'))) {
            $router = $this->app->make(\Papastro\Router\Router::class);
            $router->group(['prefix' => 'api'], function () {
                require base_path('routes/api.php');
            });
        }
    }
}
