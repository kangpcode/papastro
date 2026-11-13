<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0
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
use Papastro\Cache\CacheManager;

/**
 * FullPageCache — High-throughput full-page and Edge CDN offloading middleware.
 * Designed to handle 10M–100M+ hits with sub-millisecond in-memory delivery,
 * HTTP 304 ETag caching, and Cloudflare/CloudFront s-maxage directives.
 *
 * Usage: ->middleware('cache.page:300') // 300 seconds cache
 */
class FullPageCache
{
    protected CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    public function handle(Request $request, Closure $next, int|string $ttlSeconds = 300): Response
    {
        // 1. Only cache GET and HEAD requests
        if (!in_array($request->method(), ['GET', 'HEAD'])) {
            return $next($request);
        }

        // 2. Bypass cache for authenticated users or requests with authorization headers
        if ($this->shouldBypass($request)) {
            $response = $next($request);
            return $response->withHeader('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        }

        $ttl = max(1, (int) $ttlSeconds);
        $cacheKey = $this->resolveCacheKey($request);

        // 3. Check for cached response
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached) && isset($cached['content'], $cached['status'])) {
            $etag = $cached['etag'] ?? '"' . md5($cached['content']) . '"';

            // HTTP 304 Not Modified validation (0-byte transfer)
            if ($request->header('If-None-Match') === $etag) {
                return Response::make('', 304, [
                    'ETag' => $etag,
                    'Cache-Control' => "public, max-age={$ttl}, s-maxage=" . ($ttl * 5) . ", stale-while-revalidate=60",
                    'X-Papastro-Cache' => 'HIT-REVALIDATED',
                ]);
            }

            $response = new Response($cached['content'], $cached['status'], $cached['headers'] ?? []);
            return $response->withHeaders([
                'ETag' => $etag,
                'Cache-Control' => "public, max-age={$ttl}, s-maxage=" . ($ttl * 5) . ", stale-while-revalidate=60",
                'X-Papastro-Cache' => 'HIT',
            ]);
        }

        // 4. Execute request
        $response = $next($request);

        // 5. Store successful 200 responses in cache
        if ($response->getStatusCode() === 200 && !$response->hasHeader('Set-Cookie')) {
            $content = (string) $response->getContent();
            $etag = '"' . md5($content) . '"';

            $cacheData = [
                'status' => $response->getStatusCode(),
                'content' => $content,
                'headers' => $response->getHeaders(),
                'etag' => $etag,
            ];

            $this->cache->put($cacheKey, $cacheData, $ttl);

            return $response->withHeaders([
                'ETag' => $etag,
                'Cache-Control' => "public, max-age={$ttl}, s-maxage=" . ($ttl * 5) . ", stale-while-revalidate=60",
                'X-Papastro-Cache' => 'MISS',
            ]);
        }

        return $response;
    }

    /**
     * Determine if cache should be bypassed (e.g. logged in user).
     */
    protected function shouldBypass(Request $request): bool
    {
        if ($request->header('Authorization') !== null) {
            return true;
        }

        if (function_exists('session') && session()->isStarted() && (session()->has('user') || session()->has('user_id'))) {
            return true;
        }

        return false;
    }

    /**
     * Generate deterministic cache key for the request.
     */
    protected function resolveCacheKey(Request $request): string
    {
        $uri = $request->fullUrl();
        $accept = (string) $request->header('Accept', '');
        return 'full_page_cache_' . sha1("{$uri}|{$accept}");
    }
}

