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
        $router = $this->app->make(\Papastro\Router\Router::class);
        \Papastro\Router\RouteFacade::setRouter($router);

        // Web routes run through the "web" middleware group (session + CSRF).
        $router->group(['middleware' => ['web']], function () {
            require base_path('routes/web.php');
        });

        if (file_exists(base_path('routes/api.php'))) {
            // API routes run through the "api" group (CORS + throttle), prefixed with /api.
            $router->group(['prefix' => 'api', 'middleware' => ['api']], function () {
                require base_path('routes/api.php');
            });
        }
    }
}
