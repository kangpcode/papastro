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

namespace Papastro\Security;

use Closure;
use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * DdosShield — High-speed adaptive Layer-7 DDoS mitigation and bot flood protection.
 */
class DdosShield
{
    /** @var array<string, array{count: int, reset: int, banned_until: int}> In-memory IP tracking cache */
    protected static array $ipBuckets = [];

    protected int $maxRequests;
    protected int $decaySeconds;
    protected int $banThreshold;
    protected int $banDuration;

    public function __construct(
        int $maxRequests = 300,
        int $decaySeconds = 60,
        int $banThreshold = 600,
        int $banDuration = 900
    ) {
        $this->maxRequests = $maxRequests;
        $this->decaySeconds = $decaySeconds;
        $this->banThreshold = $banThreshold;
        $this->banDuration = $banDuration;
    }

    /**
     * Handle incoming request through the DDoS shield.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip() ?: '127.0.0.1';

        // Whitelist loopback
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return $next($request);
        }

        $now = time();

        if (isset(self::$ipBuckets[$ip])) {
            $bucket = &self::$ipBuckets[$ip];

            // Check if currently banned
            if ($bucket['banned_until'] > $now) {
                $retryAfter = $bucket['banned_until'] - $now;
                return Response::json([
                    'error'   => 'DDoS Protection Triggered',
                    'message' => 'Your IP address has been temporarily blocked due to malicious traffic.',
                    'retry_after' => $retryAfter,
                ], 429, [
                    'Retry-After'     => (string) $retryAfter,
                    'X-DDoS-Shield'   => 'Banned',
                ]);
            }

            // Reset bucket window if expired
            if ($now >= $bucket['reset']) {
                $bucket['count'] = 1;
                $bucket['reset'] = $now + $this->decaySeconds;
            } else {
                $bucket['count']++;

                // Trigger ban if flooding aggressively
                if ($bucket['count'] > $this->banThreshold) {
                    $bucket['banned_until'] = $now + $this->banDuration;
                    return Response::json([
                        'error'   => 'DDoS Protection Triggered',
                        'message' => 'Rate limit exceeded. Temporary cooldown active.',
                        'retry_after' => $this->banDuration,
                    ], 429, [
                        'Retry-After'   => (string) $this->banDuration,
                        'X-DDoS-Shield' => 'Banned',
                    ]);
                }

                // Normal rate limit throttling
                if ($bucket['count'] > $this->maxRequests) {
                    $retryAfter = max(1, $bucket['reset'] - $now);
                    return Response::json([
                        'error'   => 'Too Many Requests',
                        'message' => 'Please slow down your requests.',
                        'retry_after' => $retryAfter,
                    ], 429, [
                        'Retry-After'   => (string) $retryAfter,
                        'X-DDoS-Shield' => 'Throttled',
                    ]);
                }
            }
        } else {
            self::$ipBuckets[$ip] = [
                'count'        => 1,
                'reset'        => $now + $this->decaySeconds,
                'banned_until' => 0,
            ];

            // Garbage collect old buckets periodically
            if (count(self::$ipBuckets) > 10000) {
                self::pruneExpired($now);
            }
        }

        /** @var Response $response */
        $response = $next($request);
        $response->header('X-DDoS-Shield', 'Protected');
        return $response;
    }

    /**
     * Clear expired buckets to prevent memory accumulation.
     */
    public static function pruneExpired(int $now): void
    {
        foreach (self::$ipBuckets as $ip => $data) {
            if ($data['reset'] < $now && $data['banned_until'] < $now) {
                unset(self::$ipBuckets[$ip]);
            }
        }
    }

    /**
     * Reset all tracking state (used in testing).
     */
    public static function resetState(): void
    {
        self::$ipBuckets = [];
    }
}
