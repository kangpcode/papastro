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

namespace App\Http\Middleware;

use Closure;
use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * CompressResponse — Dynamic Gzip/Deflate compression middleware.
 * Reduces bandwidth by 70–85% for text, HTML, and JSON responses,
 * enabling ultra-high throughput at scale.
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip if streaming, binary, or if zlib is not loaded
        if (!function_exists('gzencode') || $response instanceof \Papastro\Http\StreamResponse) {
            return $response;
        }

        $acceptEncoding = (string) $request->header('Accept-Encoding', '');
        if (!str_contains($acceptEncoding, 'gzip')) {
            return $response;
        }

        $content = (string) $response->getContent();
        // Only compress payloads larger than 1KB
        if (strlen($content) < 1024 || $response->hasHeader('Content-Encoding')) {
            return $response;
        }

        $compressed = @gzencode($content, 5);
        if ($compressed === false) {
            return $response;
        }

        return $response->setContent($compressed)->withHeaders([
            'Content-Encoding' => 'gzip',
            'Vary' => 'Accept-Encoding',
            'Content-Length' => (string) strlen($compressed),
        ]);
    }
}
