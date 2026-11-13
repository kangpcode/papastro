<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

define('PAPASTRO_START', microtime(true));
define('PAPASTRO_ROOT', __DIR__);
define('PAPASTRO_VERSION', '5.0.0');

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
if (file_exists(__DIR__ . '/libs/autoload.php')) {
    require __DIR__ . '/libs/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    fwrite(STDERR, "Error: Composer dependencies not found. Please run 'composer install'.\n");
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Bootstrap Application
|--------------------------------------------------------------------------
*/
$app = require_once __DIR__ . '/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run Console Kernel
|--------------------------------------------------------------------------
| Resolve through the container so bootstrap/app.php bindings apply.
*/
$kernel = $app->make(\Papastro\Console\Kernel::class);

$status = $kernel->handle($_SERVER['argv'] ?? ['papastro']);

exit($status);
