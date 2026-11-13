<?php
declare(strict_types=1);

define('PAPASTRO_START', microtime(true));
define('PAPASTRO_ROOT', dirname(__DIR__));
define('PAPASTRO_VERSION', '5.0.0');

$baseDir = dirname(__DIR__);
if (file_exists($baseDir . '/libs/autoload.php')) {
    require $baseDir . '/libs/autoload.php';
} elseif (file_exists($baseDir . '/vendor/autoload.php')) {
    require $baseDir . '/vendor/autoload.php';
} else {
    fwrite(STDERR, "Error: Composer dependencies not found. Please run 'composer install'.\n");
    exit(1);
}
