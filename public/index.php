<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
    if ($uri !== '/') {
        if (file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
            return false;
        }
        if (preg_match('#/(icons/.*|assets/.*|islands/.*|[^/]+\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|json|woff2?|ttf|map))$#', $uri, $m)) {
            $candidate = __DIR__ . '/' . $m[1];
            if (file_exists($candidate) && is_file($candidate)) {
                $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                $mimes = [
                    'css'   => 'text/css; charset=UTF-8',
                    'js'    => 'application/javascript; charset=UTF-8',
                    'json'  => 'application/json; charset=UTF-8',
                    'png'   => 'image/png',
                    'jpg'   => 'image/jpeg',
                    'jpeg'  => 'image/jpeg',
                    'gif'   => 'image/gif',
                    'svg'   => 'image/svg+xml',
                    'webp'  => 'image/webp',
                    'ico'   => 'image/x-icon',
                ];
                header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
                header('Content-Length: ' . filesize($candidate));
                readfile($candidate);
                exit(0);
            }
        }
    }
}

define('PAPASTRO_START', microtime(true));
define('PAPASTRO_ROOT', dirname(__DIR__));
define('PAPASTRO_VERSION', '3.0.0');

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require dirname(__DIR__) . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Bootstrap & Run The Application
|--------------------------------------------------------------------------
*/
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
*/
$kernel = $app->make(\Papastro\Http\Kernel::class);

$request  = \Papastro\Http\Request::capture();
$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);
