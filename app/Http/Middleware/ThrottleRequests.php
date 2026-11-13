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
use Papastro\Security\RateLimiter;

/**
 * ThrottleRequests — Protects routes and APIs against brute-force and DDoS floods.
 * Usage on route: ->middleware('throttle:60,1')
 */
class ThrottleRequests
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, int|string $maxAttempts = 60, int|string $decayMinutes = 1): Response
    {
        $max = (int) $maxAttempts;
        $decaySeconds = max(1, (int) $decayMinutes * 60);
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $max)) {
            $retryAfter = $this->limiter->availableIn($key);
            $headers = [
                'Retry-After' => (string) $retryAfter,
                'X-RateLimit-Limit' => (string) $max,
                'X-RateLimit-Remaining' => '0',
            ];

            if ($request->expectsJson() || str_starts_with(ltrim($request->path(), '/'), 'api/')) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'Too Many Requests.',
                    'retry_after' => $retryAfter,
                ], 429, $headers);
            }

            return Response::make('Too Many Requests. Please slow down.', 429, $headers);
        }

        $this->limiter->hit($key, $decaySeconds);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => (string) $max,
            'X-RateLimit-Remaining' => (string) $this->limiter->remaining($key, $max),
        ]);
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1($request->method() . '|' . $request->host() . '|' . $request->path() . '|' . $request->ip());
    }
}
