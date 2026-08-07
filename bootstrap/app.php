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

use Papastro\Foundation\Application;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Papastro application instance
| which serves as the "glue" for all the components of Papastro, and is
| the IoC container for the system binding all of the various parts.
|
*/
$app = new Application(
    dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/
$app->singleton(
    \Papastro\Http\Kernel::class,
    \App\Http\Kernel::class
);

$app->singleton(
    \Papastro\Console\Kernel::class,
    \App\Console\Kernel::class
);

$app->singleton(
    \Papastro\Router\Router::class,
    fn ($app) => new \Papastro\Router\Router($app)
);

$app->singleton(
    \Papastro\Config\Repository::class,
    fn ($app) => new \Papastro\Config\Repository($app->configPath())
);

$app->alias(\Papastro\Config\Repository::class, 'config');
$app->alias(\Papastro\Router\Router::class, 'router');

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/
return $app;
