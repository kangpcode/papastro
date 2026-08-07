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

namespace App\Providers;

use Papastro\Foundation\ServiceProvider;
use Papastro\Events\EventDispatcher;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected array $listen = [
        // 'App\Events\OrderPlaced' => [
        //     'App\Listeners\SendOrderNotification',
        // ],
    ];

    public function register(): void
    {
        $this->app->singleton(EventDispatcher::class, function ($app) {
            return new EventDispatcher($app);
        });

        $this->app->alias(EventDispatcher::class, 'events');
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(EventDispatcher::class);

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }
    }
}
