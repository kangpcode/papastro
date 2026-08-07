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

use Papastro\Http\Request;
use Papastro\Http\Exception\NotFoundException;
use Papastro\Http\Exception\MethodNotAllowedException;

/**
 * Manages all registered routes and performs route matching.
 */
class RouteCollection
{
    /**
     * All registered routes indexed by method.
     */
    protected array $routes = [];

    /**
     * Routes indexed by name.
     */
    protected array $nameList = [];

    /**
     * All route instances.
     */
    protected array $allRoutes = [];

    /**
     * Add a route to the collection.
     */
    public function add(Route $route): Route
    {
        $this->allRoutes[] = $route;

        foreach ($route->getMethods() as $method) {
            $this->routes[$method][] = $route;
        }

        if ($name = $route->getName()) {
            $this->nameList[$name] = $route;
        }

        return $route;
    }

    /**
     * Find the first route matching a given request.
     */
    public function match(Request $request): Route
    {
        $method  = $request->method();
        $routes  = $this->routes[$method] ?? [];
        $matched = null;

        foreach ($routes as $route) {
            if ($route->matches($request)) {
                $matched = $route;
                break;
            }
        }

        if ($matched) {
            return $matched;
        }

        // Check if any route matches the URI but not the method
        $uri = '/' . ltrim($request->path(), '/');
        foreach ($this->allRoutes as $route) {
            foreach ($route->getMethods() as $routeMethod) {
                if ($routeMethod !== $method) {
                    $testRequest = clone $request;
                    if ($route->matches($testRequest)) {
                        throw new MethodNotAllowedException($route->getMethods(), $uri);
                    }
                }
            }
        }

        throw new NotFoundException("No route matched for [{$method}] {$uri}");
    }

    /**
     * Get a route instance by its name.
     */
    public function getByName(string $name): ?Route
    {
        return $this->nameList[$name] ?? null;
    }

    /**
     * Get all routes.
     */
    public function all(): array
    {
        return $this->allRoutes;
    }

    /**
     * Get all routes indexed by method.
     */
    public function getRoutesByMethod(): array
    {
        return $this->routes;
    }

    /**
     * Get all named routes.
     */
    public function getNamedRoutes(): array
    {
        return $this->nameList;
    }

    /**
     * Get total number of routes.
     */
    public function count(): int
    {
        return count($this->allRoutes);
    }
}
