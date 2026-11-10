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

use Closure;
use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * Papastro Router
 *
 * Expressive HTTP router with support for all HTTP verbs, named routes,
 * route groups, middleware groups/aliases, and island (SSR+hydration) routes.
 */
class Router
{
    /**
     * The route collection.
     */
    protected RouteCollection $routes;

    /**
     * The current route group attribute stack.
     */
    protected array $groupStack = [];

    /**
     * The application instance.
     */
    protected mixed $app;

    /**
     * The current matched route.
     */
    protected ?Route $currentRoute = null;

    /**
     * Registered middleware groups (web, api).
     */
    protected array $middlewareGroups = [];

    /**
     * Registered route middleware aliases (auth, csrf, throttle, etc.).
     */
    protected array $routeMiddleware = [];

    /**
     * Create a new Router instance.
     */
    public function __construct(mixed $app)
    {
        $this->app = $app;
        $this->routes = new RouteCollection;
    }

    /**
     * Set middleware groups from HTTP Kernel.
     */
    public function setMiddlewareGroups(array $groups): void
    {
        $this->middlewareGroups = $groups;
    }

    /**
     * Set route middleware aliases from HTTP Kernel.
     */
    public function setRouteMiddleware(array $middleware): void
    {
        $this->routeMiddleware = $middleware;
    }

    /**
     * Register a GET route.
     */
    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a POST route.
     */
    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * Register an OPTIONS route.
     */
    public function options(string $uri, mixed $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * Register a route responding to any HTTP verb.
     */
    public function any(string $uri, mixed $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * Register a route that will only respond to multiple HTTP verbs.
     */
    public function match(array $methods, string $uri, mixed $action): Route
    {
        return $this->addRoute(array_map('strtoupper', $methods), $uri, $action);
    }

    /**
     * Register a RESTful resource controller.
     */
    public function resource(string $name, string $controller): void
    {
        $slug = trim($name, '/');
        $this->get("/{$slug}",            [$controller, 'index'])->name("{$slug}.index");
        $this->get("/{$slug}/create",     [$controller, 'create'])->name("{$slug}.create");
        $this->post("/{$slug}",           [$controller, 'store'])->name("{$slug}.store");
        $this->get("/{$slug}/{id}",       [$controller, 'show'])->name("{$slug}.show");
        $this->get("/{$slug}/{id}/edit",  [$controller, 'edit'])->name("{$slug}.edit");
        $this->put("/{$slug}/{id}",       [$controller, 'update'])->name("{$slug}.update");
        $this->delete("/{$slug}/{id}",    [$controller, 'destroy'])->name("{$slug}.destroy");
    }

    /**
     * Register an island route (SSR + client hydration).
     */
    public function island(string $uri, string $islandComponent): Route
    {
        $route = $this->addRoute(['GET', 'HEAD'], $uri, function (Request $request) use ($islandComponent) {
            return $this->app->make(\Papastro\Island\IslandRenderer::class)
                ->render($islandComponent, $request->routeParameters());
        });

        $route->isIsland = true;
        $route->islandComponent = $islandComponent;

        return $route;
    }

    /**
     * Create a route group with shared attributes.
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->updateGroupStack($attributes);

        call_user_func($callback, $this);

        array_pop($this->groupStack);
    }

    /**
     * Add a route to the route collection.
     */
    protected function addRoute(array $methods, string $uri, mixed $action): Route
    {
        $route = $this->newRoute($methods, $this->prefix($uri), $action);

        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        $this->routes->add($route);

        return $route;
    }

    /**
     * Create a new Route object.
     */
    protected function newRoute(array $methods, string $uri, mixed $action): Route
    {
        return (new Route($methods, $uri, $action))->setRouter($this)->setContainer($this->app);
    }

    /**
     * Update the group stack with the given attributes.
     */
    protected function updateGroupStack(array $attributes): void
    {
        $this->groupStack[] = $this->mergeWithLastGroup($attributes);
    }

    /**
     * Merge the given group attributes with the last group stack.
     */
    protected function mergeWithLastGroup(array $new): array
    {
        return RouteGroup::merge($new, end($this->groupStack) ?: []);
    }

    /**
     * Merge the group stack with the controller action.
     */
    protected function mergeGroupAttributesIntoRoute(Route $route): void
    {
        $route->setAction($this->mergeWithLastGroup($route->getAction()));
    }

    /**
     * Determine if the router currently has a group stack.
     */
    protected function hasGroupStack(): bool
    {
        return !empty($this->groupStack);
    }

    /**
     * Get the prefix from the last group on the stack.
     */
    protected function prefix(string $uri): string
    {
        $prefix = '';

        if ($this->hasGroupStack()) {
            $prefix = end($this->groupStack)['prefix'] ?? '';
        }

        return trim(trim($prefix, '/') . '/' . trim($uri, '/'), '/') ?: '/';
    }

    /**
     * Dispatch the request to the application.
     */
    public function dispatch(Request $request): Response
    {
        return $this->runRoute($request, $this->findRoute($request));
    }

    /**
     * Find the route matching a given request.
     */
    protected function findRoute(Request $request): Route
    {
        $route = $this->routes->match($request);

        $this->currentRoute = $route;

        $this->app->instance(Route::class, $route);

        return $route;
    }

    /**
     * Run the given route within a Stack "onion" instance.
     */
    protected function runRoute(Request $request, Route $route): Response
    {
        $request->setRouteResolver(fn() => $route);

        return $this->runRouteWithinStack($route, $request);
    }

    /**
     * Run the given route in a stack middleware pipeline.
     */
    protected function runRouteWithinStack(Route $route, Request $request): Response
    {
        $rawMiddleware = $route->getMiddleware();
        $middleware = $this->gatherRouteMiddleware($rawMiddleware);

        if (empty($middleware)) {
            return $route->run($request);
        }

        $pipeline = new \Papastro\Http\Pipeline($this->app);

        return $pipeline->send($request)
            ->through($middleware)
            ->then(fn($req) => $route->run($req));
    }

    /**
     * Resolve middleware groups and aliases into concrete class names (preserving parameters).
     */
    public function gatherRouteMiddleware(array $middleware): array
    {
        $resolved = [];

        foreach ($middleware as $name) {
            if (!is_string($name)) {
                $resolved[] = $name;
                continue;
            }

            if (isset($this->middlewareGroups[$name])) {
                $resolved = array_merge($resolved, $this->gatherRouteMiddleware($this->middlewareGroups[$name]));
                continue;
            }

            $params = '';
            $baseName = $name;
            if (str_contains($name, ':')) {
                [$baseName, $params] = explode(':', $name, 2);
                $params = ':' . $params;
            }

            if (isset($this->routeMiddleware[$baseName])) {
                $resolved[] = $this->routeMiddleware[$baseName] . $params;
            } else {
                $resolved[] = $name;
            }
        }

        return array_values(array_unique($resolved, SORT_REGULAR));
    }

    /**
     * Get the currently dispatched route instance.
     */
    public function current(): ?Route
    {
        return $this->currentRoute;
    }

    /**
     * Get all routes in the collection.
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Generate a URL for a named route.
     */
    public function route(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);

        if (!$route) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        return $route->toUrl($parameters);
    }

    /**
     * Get the current group stack.
     */
    public function getGroupStack(): array
    {
        return $this->groupStack;
    }
}
