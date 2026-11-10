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

namespace Papastro\Router;

use Papastro\Http\Request;
use Papastro\Http\Exception\NotFoundException;
use Papastro\Http\Exception\MethodNotAllowedException;

/**
 * Manages all registered routes and performs high-speed O(1) static + regex route matching.
 */
class RouteCollection
{
    /**
     * All registered routes indexed by method.
     */
    protected array $routes = [];

    /**
     * O(1) lookup table for static (parameter-less) routes: $staticRoutes[$method][$uri] = Route
     */
    protected array $staticRoutes = [];

    /**
     * Dynamic (parameterized) routes indexed by method.
     */
    protected array $dynamicRoutes = [];

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
        $uri = '/' . ltrim($route->getUri(), '/');
        $isStatic = !str_contains($uri, '{');

        foreach ($route->getMethods() as $method) {
            $this->routes[$method][] = $route;
            if ($isStatic) {
                $this->staticRoutes[$method][$uri] = $route;
            } else {
                $this->dynamicRoutes[$method][] = $route;
            }
        }

        if ($name = $route->getName()) {
            $this->nameList[$name] = $route;
        }

        return $route;
    }

    /**
     * Refresh the named routes lookup table.
     */
    public function refreshNameLookups(): void
    {
        $this->nameList = [];
        foreach ($this->allRoutes as $route) {
            if ($name = $route->getName()) {
                $this->nameList[$name] = $route;
            }
        }
    }

    /**
     * Find the first route matching a given request.
     * Uses O(1) hash lookup for static routes before checking dynamic regex routes.
     */
    public function match(Request $request): Route
    {
        $method = $request->method();
        $uri = '/' . ltrim($request->path(), '/');

        // 1. Fast-path O(1) static route lookup
        if (isset($this->staticRoutes[$method][$uri])) {
            return $this->staticRoutes[$method][$uri];
        }

        // 2. Dynamic parameterized route matching
        $dynamic = $this->dynamicRoutes[$method] ?? [];
        foreach ($dynamic as $route) {
            if ($route->matches($request)) {
                return $route;
            }
        }

        // 3. Check if any route matches the URI for another HTTP method (405 Method Not Allowed)
        $allowedMethods = [];
        foreach ($this->staticRoutes as $m => $map) {
            if ($m !== $method && isset($map[$uri])) {
                $allowedMethods[] = $m;
            }
        }

        if (empty($allowedMethods)) {
            foreach ($this->allRoutes as $route) {
                $testReq = new Request($route->getMethods()[0] ?? 'GET', $uri);
                if ($route->matches($testReq)) {
                    $allowedMethods = array_merge($allowedMethods, $route->getMethods());
                }
            }
        }

        if (!empty($allowedMethods)) {
            throw new MethodNotAllowedException(array_values(array_unique($allowedMethods)), $uri);
        }

        throw new NotFoundException("No route matched for [{$method}] {$uri}");
    }

    /**
     * Get a route instance by its name.
     */
    public function getByName(string $name): ?Route
    {
        if (!isset($this->nameList[$name])) {
            $this->refreshNameLookups();
        }
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
        $this->refreshNameLookups();
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
