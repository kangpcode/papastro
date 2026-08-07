<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Foundation;

use Papastro\Foundation\Application;

/**
 * Base Service Provider class.
 * All app and framework service providers should extend this class.
 */
abstract class ServiceProvider
{
    protected Application $app;

    protected array $bootingCallbacks = [];
    protected array $bootedCallbacks  = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register any application services.
     * Override this in your provider to bind classes, singletons, etc.
     */
    abstract public function register(): void;

    /**
     * Bootstrap any application services.
     * Override this in your provider to run code after all providers are registered.
     */
    public function boot(): void
    {
        // Default: no boot logic. Override in subclass.
    }

    /**
     * Register a booting callback.
     */
    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a booted callback.
     */
    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;
    }

    /**
     * Call the registered booting callbacks.
     */
    public function callBootingCallbacks(): void
    {
        foreach ($this->bootingCallbacks as $callback) {
            $this->app->call($callback);
        }
    }

    /**
     * Call the registered booted callbacks.
     */
    public function callBootedCallbacks(): void
    {
        foreach ($this->bootedCallbacks as $callback) {
            $this->app->call($callback);
        }
    }

    /**
     * Merge the given configuration with the existing configuration.
     */
    protected function mergeConfigFrom(string $path, string $key): void
    {
        $config = $this->app->make('config');
        $config->set($key, array_merge(
            require $path,
            (array) $config->get($key, [])
        ));
    }

    /**
     * Load routes from a file.
     */
    protected function loadRoutesFrom(string $path): void
    {
        $router = $this->app->make(\Papastro\Router\Router::class);

        // Make Route facade accessible
        \Papastro\Router\RouteFacade::setRouter($router);

        require $path;
    }
}
