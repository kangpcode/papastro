<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.2.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Events;

use Papastro\Foundation\Application;

/**
 * EventDispatcher — Lightweight event dispatcher for Papastro modules.
 */
class EventDispatcher
{
    protected Application $app;
    protected array $listeners = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register an event listener.
     */
    public function listen(string $event, callable|string $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event to all registered listeners.
     */
    public function dispatch(object|string $event, array $payload = []): array
    {
        $eventName = is_object($event) ? get_class($event) : $event;
        $eventData = is_object($event) ? [$event] : $payload;

        $responses = [];

        if (empty($this->listeners[$eventName])) {
            return $responses;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            if (is_string($listener)) {
                $instance = $this->app->make($listener);
                $response = $this->app->call([$instance, 'handle'], ['event' => $event, 'payload' => $payload]);
            } else {
                $response = $listener(is_object($event) ? $event : $payload);
            }

            $responses[] = $response;

            if ($response === false) {
                break; // Stop propagation if listener returns false
            }
        }

        return $responses;
    }

    /**
     * Get all registered listeners.
     */
    public function getListeners(string $event = ''): array
    {
        if ($event) {
            return $this->listeners[$event] ?? [];
        }

        return $this->listeners;
    }
}
