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
use Papastro\Cache\CacheManager;

/**
 * RateLimiter — High-scale request & action rate limiting engine.
 */
class RateLimiter
{
    protected CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to execute a callback if rate limit has not been exceeded.
     */
    public function attempt(string $key, int $maxAttempts, Closure $callback, int $decaySeconds = 60): mixed
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $result = $callback();
        $this->hit($key, $decaySeconds);

        return $result === null ? true : $result;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $cleanKey = $this->cleanKey($key);
        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->has($cleanKey . ':timer')) {
                return true;
            }
            $this->clear($key);
        }

        return false;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $cleanKey = $this->cleanKey($key);
        $now = time();

        if (!$this->cache->has($cleanKey . ':timer')) {
            $this->cache->put($cleanKey . ':timer', $now + $decaySeconds, $decaySeconds);
            $this->cache->put($cleanKey, 1, $decaySeconds);
            return 1;
        }

        return $this->cache->increment($cleanKey, 1, $decaySeconds);
    }

    /**
     * Get the number of attempts for the given key.
     */
    public function attempts(string $key): int
    {
        return (int) $this->cache->get($this->cleanKey($key), 0);
    }

    /**
     * Get the number of remaining attempts for the given key.
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->attempts($key));
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     */
    public function availableIn(string $key): int
    {
        $timer = (int) $this->cache->get($this->cleanKey($key) . ':timer', 0);
        return max(0, $timer - time());
    }

    /**
     * Clear the hits and lockout timer for the given key.
     */
    public function clear(string $key): void
    {
        $cleanKey = $this->cleanKey($key);
        $this->cache->forget($cleanKey);
        $this->cache->forget($cleanKey . ':timer');
    }

    protected function cleanKey(string $key): string
    {
        return 'ratelimit:' . sha1($key);
    }
}
