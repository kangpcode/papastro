<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0 — Development Server Router
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
    $publicDir = __DIR__ . '/public';
    if (file_exists($publicDir . $uri) && is_file($publicDir . $uri)) {
        return false;
    }
    // Subpath relative static asset fallback (e.g. /blog/icons/icon-192.png)
    if (preg_match('#/(icons/.*|assets/.*|islands/.*|[^/]+\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|json|woff2?|ttf|map))$#', $uri, $m)) {
        $candidate = $publicDir . '/' . $m[1];
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

require_once __DIR__ . '/public/index.php';

