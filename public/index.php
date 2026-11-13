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

if (PHP_SAPI === 'cli-server') {
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
    if ($uri !== '/') {
        $real = realpath(__DIR__ . $uri);
        if ($real && str_starts_with($real, __DIR__) && is_file($real) && basename($real) !== 'index.php') {
            return false;
        }
        if (preg_match('#/(icons/.*|assets/.*|islands/.*|[^/]+\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|json|woff2?|ttf|map))$#', $uri, $m)) {
            $candidate = realpath(__DIR__ . '/' . $m[1]);
            if ($candidate && str_starts_with($candidate, __DIR__) && is_file($candidate) && basename($candidate) !== 'index.php') {
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
                    'woff'  => 'font/woff',
                    'woff2' => 'font/woff2',
                    'ttf'   => 'font/ttf',
                ];
                header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
                header('Content-Length: ' . filesize($candidate));
                $mtime = filemtime($candidate);
                $etag = '"' . dechex($mtime) . '-' . dechex(filesize($candidate)) . '"';
                header('ETag: ' . $etag);
                header('Cache-Control: public, max-age=31536000, immutable');
                if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
                    http_response_code(304);
                    exit(0);
                }
                readfile($candidate);
                exit(0);
            }
        }
    }
}

define('PAPASTRO_START', microtime(true));
define('PAPASTRO_ROOT', dirname(__DIR__));
define('PAPASTRO_VERSION', '4.0.0');

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
$baseDir = dirname(__DIR__);
if (file_exists($baseDir . '/libs/autoload.php')) {
    require $baseDir . '/libs/autoload.php';
} elseif (file_exists($baseDir . '/vendor/autoload.php')) {
    require $baseDir . '/vendor/autoload.php';
} else {
    http_response_code(500);
    echo "Error: Composer dependencies not found. Please run 'composer install'.";
    exit(1);
}

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
