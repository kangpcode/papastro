<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Runtime;

use Papastro\Foundation\Application;

/**
 * ServerManager — Factory and lifecycle manager for Papastro runtime drivers.
 */
class ServerManager
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Create and start the specified runtime server.
     */
    public function serve(
        string $driver = 'native',
        string $host = '127.0.0.1',
        int $port = 8000,
        int $workers = 0,
        int $maxRequests = 10000,
        bool $watch = false
    ): void {
        if ($driver === 'swoole' && extension_loaded('swoole')) {
            $server = new SwooleServer($this->app, $host, $port, $workers, $maxRequests, $watch);
        } else {
            $server = new NativeServer($this->app, $host, $port, $workers, $maxRequests, 128, $watch);
        }

        $server->start();
    }

    /**
     * Stop the running runtime server using its PID file.
     */
    public function stop(): bool
    {
        $pidFile = $this->app->storagePath('framework/runtime.pid');
        if (!file_exists($pidFile)) {
            return false;
        }

        $pid = (int) trim((string) @file_get_contents($pidFile));
        if ($pid > 0 && function_exists('posix_kill')) {
            @posix_kill($pid, SIGTERM);
            @unlink($pidFile);
            return true;
        }

        return false;
    }

    /**
     * Gracefully reload workers of the running runtime server.
     */
    public function reload(): bool
    {
        $pidFile = $this->app->storagePath('framework/runtime.pid');
        if (!file_exists($pidFile)) {
            return false;
        }

        $pid = (int) trim((string) @file_get_contents($pidFile));
        if ($pid > 0 && function_exists('posix_kill')) {
            return @posix_kill($pid, SIGUSR1);
        }

        return false;
    }

    /**
     * Check if the runtime server is currently active.
     */
    public function isRunning(): bool
    {
        $pidFile = $this->app->storagePath('framework/runtime.pid');
        if (!file_exists($pidFile)) {
            return false;
        }

        $pid = (int) trim((string) @file_get_contents($pidFile));
        if ($pid > 0 && function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        return false;
    }

    /**
     * Get the PID of the active runtime server.
     */
    public function getPid(): ?int
    {
        $pidFile = $this->app->storagePath('framework/runtime.pid');
        if (!file_exists($pidFile)) {
            return null;
        }

        $pid = (int) trim((string) @file_get_contents($pidFile));
        return $pid > 0 ? $pid : null;
    }
}
