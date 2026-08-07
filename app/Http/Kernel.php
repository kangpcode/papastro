<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.2.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http;

use Papastro\Http\Kernel as BaseKernel;
use Papastro\Foundation\Application;
use Papastro\Router\Router;

class Kernel extends BaseKernel
{
    /**
     * Create a new HTTP Kernel and bootstrap the application.
     */
    public function __construct(Application $app, Router $router)
    {
        parent::__construct($app, $router);

        // Bootstrap the application for HTTP requests
        if (!$app->hasBeenBootstrapped()) {
            $app->bootstrapWith([
                \Papastro\Foundation\Bootstrap\LoadEnvironmentVariables::class,
                \Papastro\Foundation\Bootstrap\LoadConfiguration::class,
                \Papastro\Foundation\Bootstrap\HandleExceptions::class,
                \Papastro\Foundation\Bootstrap\RegisterProviders::class,
                \Papastro\Foundation\Bootstrap\BootProviders::class,
            ]);
        }
    }

    /**
     * The application's global HTTP middleware stack.
     * These run before every request.
     */
    protected array $middleware = [
        \App\Http\Middleware\TrimStrings::class,
        \App\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's middleware groups.
     */
    protected array $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\VerifyCsrfToken::class,
        ],
        'api' => [
            // \App\Http\Middleware\ThrottleRequests::class,
        ],
    ];

    /**
     * The application's route middleware aliases.
     */
    protected array $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'csrf' => \App\Http\Middleware\VerifyCsrfToken::class,
    ];
}
