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
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri) && is_file(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
