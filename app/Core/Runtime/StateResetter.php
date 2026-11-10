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
 * StateResetter — Ensures total sandbox isolation between HTTP requests
 * in long-running runtime servers (Octane-style) to prevent memory leaks.
 */
class StateResetter
{
    /** @var array<callable> Custom reset callbacks */
    protected static array $customResetters = [];

    /** @var int Request counter for periodic garbage collection */
    protected static int $requestCount = 0;

    /**
     * Register a custom state reset callback.
     */
    public static function register(callable $callback): void
    {
        self::$customResetters[] = $callback;
    }

    /**
     * Reset the application state between requests.
     */
    public static function reset(Application $app): void
    {
        // 1. Flush scoped container instances
        $app->forgetScopedInstances();

        // 2. Reset database transaction state if dirty
        if ($app->resolved(\Papastro\ORM\Connection::class)) {
            try {
                $db = $app->make(\Papastro\ORM\Connection::class);
                $pdo = $db->getPdo();
                if ($pdo && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (\Throwable $e) {
                // Ignore rollback failures during state reset
            }
        }

        // 3. Reset debugger query logs
        if ($app->resolved(\Papastro\Debugger\QueryCollector::class)) {
            try {
                $collector = $app->make(\Papastro\Debugger\QueryCollector::class);
                $collector->clear();
            } catch (\Throwable $e) {}
        }

        // 4. Forget request and session instances from container
        $app->forgetInstance('request');
        $app->forgetInstance(\Papastro\Http\Request::class);
        $app->forgetInstance('session');
        $app->forgetInstance(\Papastro\Session\Session::class);

        // 5. Execute custom registered resetters
        foreach (self::$customResetters as $resetter) {
            try {
                $resetter($app);
            } catch (\Throwable $e) {}
        }

        // 6. Periodic garbage collection cycle (every 100 requests)
        self::$requestCount++;
        if (self::$requestCount % 100 === 0) {
            gc_collect_cycles();
        }
    }

    /**
     * Get current request count handled by this worker.
     */
    public static function getRequestCount(): int
    {
        return self::$requestCount;
    }
}
