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

use Closure;
use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * Represents a single registered route.
 */
class Route
{
    /**
     * The URI pattern the route responds to.
     */
    protected string $uri;

    /**
     * The HTTP methods the route responds to.
     */
    protected array $methods;

    /**
     * The route action array.
     */
    protected array $action;

    /**
     * The compiled route regex.
     */
    protected ?string $compiled = null;

    /**
     * The route's parameter names.
     */
    protected array $parameterNames = [];

    /**
     * The parameters that have been resolved.
     */
    protected array $parameters = [];

    /**
     * The route's named middleware.
     */
    protected array $middleware = [];

    /**
     * The route name.
     */
    protected ?string $name = null;

    /**
     * Whether this is an island route.
     */
    public bool $isIsland = false;

    /**
     * The island component name.
     */
    public string $islandComponent = '';

    /**
     * The SEO callback for island routes.
     */
    public mixed $seoCallback = null;

    /**
     * The router instance.
     */
    protected ?Router $router = null;

    /**
     * The container instance.
     */
    protected mixed $container = null;

    /**
     * Create a new Route instance.
     */
    public function __construct(array $methods, string $uri, mixed $action)
    {
        $this->uri     = $uri;
        $this->methods = $methods;
        $this->action  = $this->parseAction($action);

        $this->compileRoute();
    }

    /**
     * Parse the route action into a standard form.
     */
    protected function parseAction(mixed $action): array
    {
        if ($action instanceof Closure) {
            return ['uses' => $action, 'controller' => null];
        }

        if (is_string($action)) {
            if (str_contains($action, '@')) {
                [$controller, $method] = explode('@', $action, 2);
                return ['uses' => $action, 'controller' => $controller, 'method' => $method];
            }
            return ['uses' => $action, 'controller' => $action, 'method' => '__invoke'];
        }

        if (is_array($action) && isset($action[0], $action[1])) {
            return ['uses' => implode('@', $action), 'controller' => $action[0], 'method' => $action[1]];
        }

        return $action;
    }

    /**
     * Compile the route URI into a regex pattern.
     */
    protected function compileRoute(): void
    {
        $pattern = preg_replace_callback('/\{([^}?]+)(\?)?\}/', function ($matches) {
            $name     = $matches[1];
            $optional = isset($matches[2]) && $matches[2] === '?';
            $this->parameterNames[] = $name;

            return $optional ? '(?:/([^/]+))?' : '([^/]+)';
        }, $this->uri);

        $this->compiled = '#^/' . ltrim($pattern, '/') . '$#';
    }

    /**
     * Determine if the route matches a given request.
     */
    public function matches(Request $request): bool
    {
        if (!in_array($request->method(), $this->methods)) {
            return false;
        }

        $path = '/' . ltrim($request->path(), '/');

        if (preg_match($this->compiled, $path, $matches)) {
            $this->bindParameters($matches);
            return true;
        }

        return false;
    }

    /**
     * Bind the route parameters from the match result.
     */
    protected function bindParameters(array $matches): void
    {
        array_shift($matches); // Remove the full match

        $this->parameters = [];
        foreach ($this->parameterNames as $i => $name) {
            $this->parameters[$name] = $matches[$i] ?? null;
        }
    }

    /**
     * Run the route action and return the response.
     */
    public function run(Request $request): Response
    {
        $action = $this->action['uses'];

        if ($action instanceof Closure) {
            $result = $this->container->call($action, $this->parameters);
        } else {
            [$controllerClass, $method] = explode('@', $action, 2);
            $controller = $this->container->make($controllerClass);
            $result = $this->container->call([$controller, $method], $this->parameters);
        }

        return $this->toResponse($result);
    }

    /**
     * Convert the given value to a Response instance.
     */
    protected function toResponse(mixed $value): Response
    {
        if ($value instanceof Response) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return Response::json($value);
        }

        return Response::make((string) $value);
    }

    /**
     * Set the middleware for this route.
     */
    public function middleware(array|string $middleware): static
    {
        $this->middleware = array_merge(
            $this->middleware,
            is_array($middleware) ? $middleware : func_get_args()
        );

        return $this;
    }

    /**
     * Set the route name.
     */
    public function name(string $name): static
    {
        $this->name = isset($this->action['as']) ? $this->action['as'] . $name : $name;
        $this->action['as'] = $this->name;

        return $this;
    }

    /**
     * Set the SEO callback for an island route.
     */
    public function seo(Closure $callback): static
    {
        $this->seoCallback = $callback;

        return $this;
    }

    /**
     * Get the URL for this route given parameters.
     */
    public function toUrl(array $parameters = []): string
    {
        $uri = $this->uri;

        foreach ($parameters as $key => $value) {
            $uri = str_replace(['{' . $key . '}', '{' . $key . '?}'], $value, $uri);
        }

        // Remove any remaining optional segments
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);

        return '/' . ltrim($uri, '/');
    }

    /**
     * Get the URI for the route.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the HTTP methods the route responds to.
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the action array for the route.
     */
    public function getAction(): array
    {
        return $this->action;
    }

    /**
     * Set the action for the route.
     */
    public function setAction(array $action): static
    {
        $this->action = array_merge($this->action, $action);

        if (isset($this->action['middleware'])) {
            $this->middleware = array_merge($this->middleware, (array) $this->action['middleware']);
        }

        return $this;
    }

    /**
     * Get the middleware attached to the route.
     */
    public function getMiddleware(): array
    {
        return array_merge((array) ($this->action['middleware'] ?? []), $this->middleware);
    }

    /**
     * Get the route name.
     */
    public function getName(): ?string
    {
        return $this->action['as'] ?? $this->name;
    }

    /**
     * Get the route parameters.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set the router instance on the route.
     */
    public function setRouter(Router $router): static
    {
        $this->router = $router;
        return $this;
    }

    /**
     * Set the container instance on the route.
     */
    public function setContainer(mixed $container): static
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Get the controller class.
     */
    public function getController(): ?string
    {
        return $this->action['controller'] ?? null;
    }

    /**
     * Get the controller method.
     */
    public function getControllerMethod(): ?string
    {
        return $this->action['method'] ?? null;
    }
}
