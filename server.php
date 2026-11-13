<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0 — Development Server Router
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

$uri = rawurldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? ''
);

// Emulate Apache mod_rewrite / Nginx try_files for PHP built-in web server
if ($uri !== '/') {
    $publicDir = realpath(__DIR__ . '/public');
    if ($publicDir) {
        $real = realpath($publicDir . $uri);
        if ($real && str_starts_with($real, $publicDir) && is_file($real) && basename($real) !== 'index.php') {
            return false;
        }
        // Subpath relative static asset fallback (e.g. /blog/icons/icon-192.png)
        if (preg_match('#/(icons/.*|assets/.*|islands/.*|[^/]+\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|json|woff2?|ttf|map))$#', $uri, $m)) {
            $candidate = realpath($publicDir . '/' . $m[1]);
            if ($candidate && str_starts_with($candidate, $publicDir) && is_file($candidate) && basename($candidate) !== 'index.php') {
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

require_once __DIR__ . '/public/index.php';

