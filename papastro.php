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

define('PAPASTRO_START', microtime(true));
define('PAPASTRO_ROOT', __DIR__);
define('PAPASTRO_VERSION', '1.2.0');

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require __DIR__ . '/vendor/autoload.php';

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
