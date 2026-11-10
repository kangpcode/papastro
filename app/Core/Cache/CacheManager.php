<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v2.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Cache;

use Closure;
use Papastro\Foundation\Application;

/**
 * CacheManager — High-concurrency multi-driver caching engine (file, array, apcu).
 * Designed for high throughput with in-memory L1 read-through caching and atomic file locking.
 */
class CacheManager
{
    protected Application $app;
    protected string $driver;
    protected string $cachePath;
    protected string $prefix;

    /**
     * L1 in-memory cache for ultra-fast repeated reads within the same request/worker.
     */
    protected array $memory = [];

    public function __construct(Application $app, ?string $driver = null)
    {
        $this->app = $app;
        $this->driver = $driver ?? (string) env('CACHE_DRIVER', 'file');
        $this->cachePath = $app->storagePath('cache/data');
        $this->prefix = 'papastro_cache_';
    }

    /**
     * Determine if an item exists in the cache and is not expired.
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__PAPASTRO_CACHE_MISS__') !== '__PAPASTRO_CACHE_MISS__';
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $now = time();

        // Check L1 memory cache first
        if (isset($this->memory[$key])) {
            $entry = $this->memory[$key];
            if ($entry['expires_at'] === 0 || $entry['expires_at'] > $now) {
                return $entry['value'];
            }
            unset($this->memory[$key]);
        }

        if ($this->driver === 'array') {
            return value($default);
        }

        if ($this->driver === 'apcu' && function_exists('apcu_fetch')) {
            $success = false;
            $val = apcu_fetch($this->prefix . $key, $success);
            if ($success) {
                return $val;
            }
            return value($default);
        }

        // File driver
        $file = $this->getFilePath($key);
        if (!is_file($file)) {
            return value($default);
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return value($default);
        }

        $payload = @unserialize($raw);
        if (!is_array($payload) || !array_key_exists('expires_at', $payload)) {
            @unlink($file);
            return value($default);
        }

        if ($payload['expires_at'] !== 0 && $payload['expires_at'] <= $now) {
            @unlink($file);
            return value($default);
        }

        $this->memory[$key] = $payload;
        return $payload['value'];
    }

    /**
     * Store an item in the cache for a given number of seconds.
     */
    public function put(string $key, mixed $value, int $ttlSeconds = 3600): bool
    {
        $expiresAt = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $payload = [
            'expires_at' => $expiresAt,
            'value' => $value,
        ];

        $this->memory[$key] = $payload;

        if ($this->driver === 'array') {
            return true;
        }

        if ($this->driver === 'apcu' && function_exists('apcu_store')) {
            return apcu_store($this->prefix . $key, $value, max(0, $ttlSeconds));
        }

        $file = $this->getFilePath($key);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return @file_put_contents($file, serialize($payload), LOCK_EX) !== false;
    }

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Get an item from the cache, or execute the Closure and store the result.
     */
    public function remember(string $key, int $ttlSeconds, Closure $callback): mixed
    {
        $value = $this->get($key, '__PAPASTRO_CACHE_MISS__');

        if ($value !== '__PAPASTRO_CACHE_MISS__') {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttlSeconds);

        return $value;
    }

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $step = 1, int $ttlSeconds = 3600): int
    {
        $current = (int) $this->get($key, 0);
        $next = $current + $step;
        $this->put($key, $next, $ttlSeconds);
        return $next;
    }

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement(string $key, int $step = 1, int $ttlSeconds = 3600): int
    {
        return $this->increment($key, -$step, $ttlSeconds);
    }

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool
    {
        unset($this->memory[$key]);

        if ($this->driver === 'apcu' && function_exists('apcu_delete')) {
            apcu_delete($this->prefix . $key);
        }

        $file = $this->getFilePath($key);
        if (is_file($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        $this->memory = [];

        if ($this->driver === 'apcu' && function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        if (is_dir($this->cachePath)) {
            $this->deleteDirectoryContents($this->cachePath);
        }

        return true;
    }

    /**
     * Get the sharded file path for a cache key.
     */
    protected function getFilePath(string $key): string
    {
        $hash = sha1($this->prefix . $key);
        $shard = substr($hash, 0, 2);
        return $this->cachePath . DIRECTORY_SEPARATOR . $shard . DIRECTORY_SEPARATOR . $hash . '.cache';
    }

    /**
     * Recursively delete contents of a directory.
     */
    protected function deleteDirectoryContents(string $dir): void
    {
        $items = glob($dir . '/*');
        if (!$items) {
            return;
        }

        foreach ($items as $item) {
            if (is_dir($item)) {
                $this->deleteDirectoryContents($item);
                @rmdir($item);
            } else {
                @unlink($item);
            }
        }
    }

    /**
     * Get the active cache driver name.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }
}
