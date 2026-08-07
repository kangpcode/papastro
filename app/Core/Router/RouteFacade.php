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

namespace Papastro\Router;

/**
 * Route Facade — provides a static interface to the Router instance.
 * Used in route files: Route::get('/path', [Controller::class, 'method'])
 */
class RouteFacade
{
    protected static ?Router $router = null;

    public static function setRouter(Router $router): void
    {
        static::$router = $router;
    }

    public static function getRouter(): Router
    {
        if (static::$router === null) {
            throw new \RuntimeException('Router has not been set on RouteFacade.');
        }
        return static::$router;
    }

    public static function get(string $uri, mixed $action): Route
    {
        return static::getRouter()->get($uri, $action);
    }

    public static function post(string $uri, mixed $action): Route
    {
        return static::getRouter()->post($uri, $action);
    }

    public static function put(string $uri, mixed $action): Route
    {
        return static::getRouter()->put($uri, $action);
    }

    public static function patch(string $uri, mixed $action): Route
    {
        return static::getRouter()->patch($uri, $action);
    }

    public static function delete(string $uri, mixed $action): Route
    {
        return static::getRouter()->delete($uri, $action);
    }

    public static function any(string $uri, mixed $action): Route
    {
        return static::getRouter()->any($uri, $action);
    }

    public static function match(array $methods, string $uri, mixed $action): Route
    {
        return static::getRouter()->match($methods, $uri, $action);
    }

    public static function island(string $uri, string $component): Route
    {
        return static::getRouter()->island($uri, $component);
    }

    public static function group(array $attributes, \Closure $callback): void
    {
        static::getRouter()->group($attributes, $callback);
    }

    public static function middleware(string|array $middleware): PendingRouteRegistration
    {
        return new PendingRouteRegistration(static::getRouter(), ['middleware' => (array) $middleware]);
    }

    public static function prefix(string $prefix): PendingRouteRegistration
    {
        return new PendingRouteRegistration(static::getRouter(), ['prefix' => $prefix]);
    }

    public static function name(string $name): PendingRouteRegistration
    {
        return new PendingRouteRegistration(static::getRouter(), ['as' => $name]);
    }

    public static function resource(string $name, string $controller): void
    {
        static::getRouter()->get("/{$name}",         [$controller, 'index'])->name("{$name}.index");
        static::getRouter()->get("/{$name}/create",  [$controller, 'create'])->name("{$name}.create");
        static::getRouter()->post("/{$name}",        [$controller, 'store'])->name("{$name}.store");
        static::getRouter()->get("/{$name}/{id}",    [$controller, 'show'])->name("{$name}.show");
        static::getRouter()->get("/{$name}/{id}/edit", [$controller, 'edit'])->name("{$name}.edit");
        static::getRouter()->put("/{$name}/{id}",    [$controller, 'update'])->name("{$name}.update");
        static::getRouter()->delete("/{$name}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
    }

    public static function apiResource(string $name, string $controller): void
    {
        static::getRouter()->get("/{$name}",         [$controller, 'index'])->name("{$name}.index");
        static::getRouter()->post("/{$name}",        [$controller, 'store'])->name("{$name}.store");
        static::getRouter()->get("/{$name}/{id}",    [$controller, 'show'])->name("{$name}.show");
        static::getRouter()->put("/{$name}/{id}",    [$controller, 'update'])->name("{$name}.update");
        static::getRouter()->delete("/{$name}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
    }
}

/**
 * Fluent route group registration builder.
 */
class PendingRouteRegistration
{
    protected Router $router;
    protected array $attributes;

    public function __construct(Router $router, array $attributes)
    {
        $this->router     = $router;
        $this->attributes = $attributes;
    }

    public function middleware(string|array $middleware): static
    {
        $this->attributes['middleware'] = array_merge(
            (array) ($this->attributes['middleware'] ?? []),
            (array) $middleware
        );
        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->attributes['prefix'] = $prefix;
        return $this;
    }

    public function name(string $name): static
    {
        $this->attributes['as'] = $name;
        return $this;
    }

    public function group(\Closure $callback): void
    {
        $this->router->group($this->attributes, $callback);
    }
}
