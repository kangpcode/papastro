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

namespace Papastro\Http;

use Closure;

/**
 * Represents an incoming HTTP request.
 */
class Request
{
    /**
     * The request method.
     */
    protected string $method;

    /**
     * The request URI path.
     */
    protected string $path;

    /**
     * Query string parameters.
     */
    protected array $query;

    /**
     * POST data / request body.
     */
    protected array $data;

    /**
     * Server variables.
     */
    protected array $server;

    /**
     * Uploaded files.
     */
    protected array $files;

    /**
     * Cookies.
     */
    protected array $cookies;

    /**
     * Request headers.
     */
    protected array $headers;

    /**
     * The route resolver callback.
     */
    protected ?Closure $routeResolver = null;

    /**
     * Create a new Request instance.
     */
    public function __construct(
        string $method,
        string $path,
        array  $query   = [],
        array  $data    = [],
        array  $server  = [],
        array  $files   = [],
        array  $cookies = [],
        array  $headers = []
    ) {
        $this->method  = strtoupper($method);
        $this->path    = $path;
        $this->query   = $query;
        $this->data    = $data;
        $this->server  = $server;
        $this->files   = $files;
        $this->cookies = $cookies;
        $this->headers = $headers;
    }

    /**
     * Create a new request from the PHP superglobals.
     */
    public static function capture(): static
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Support method overriding via X-HTTP-Method-Override or _method field
        if ($method === 'POST') {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']
                ?? $_POST['_method']
                ?? null;
            if ($override) {
                $method = strtoupper($override);
            }
        }

        $uri     = $_SERVER['REQUEST_URI'] ?? '/';
        $path    = parse_url($uri, PHP_URL_PATH) ?: '/';
        $headers = function_exists('getallheaders') ? getallheaders() : static::extractHeaders($_SERVER);

        return new static(
            $method,
            $path,
            $_GET,
            $_POST,
            $_SERVER,
            $_FILES,
            $_COOKIE,
            $headers
        );
    }

    /**
     * Extract headers from $_SERVER.
     */
    protected static function extractHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get the request method.
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get the request path.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the full URI.
     */
    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? $this->path;
    }

    /**
     * Get a query parameter by key.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters.
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Get input from POST data, then query string.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all input data (POST + query string).
     */
    public function all(): array
    {
        return array_merge($this->query, $this->data);
    }

    /**
     * Get only the specified keys from input.
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Get all input except specified keys.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Replace input dataset (used by middleware).
     */
    public function replace(array $input): static
    {
        if ($this->method() === 'GET') {
            $this->query = $input;
        } else {
            $this->data = $input;
        }
        return $this;
    }

    /**
     * Sanitize input against XSS attacks.
     */
    public function sanitize(): static
    {
        $this->data  = \Papastro\Security\Security::sanitize($this->data);
        $this->query = \Papastro\Security\Security::sanitize($this->query);
        return $this;
    }

    /**
     * Get a specific header.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtoupper(str_replace('-', '_', $key))]
            ?? $this->headers[$key]
            ?? $default;
    }

    /**
     * Get all headers.
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get a server variable.
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get a cookie value.
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get an uploaded file.
     */
    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Determine if the request is an AJAX request.
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * Determine if the request expects JSON.
     */
    public function expectsJson(): bool
    {
        return $this->isAjax() || str_contains($this->header('Accept', ''), 'application/json');
    }

    /**
     * Determine if the request is a specific HTTP method.
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    /**
     * Check if input key exists.
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]) || isset($this->query[$key]);
    }

    /**
     * Check if all keys exist in input.
     */
    public function hasAll(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) return false;
        }
        return true;
    }

    /**
     * Get the decoded JSON body.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $body    = file_get_contents('php://input');
        $decoded = json_decode($body, true) ?? [];

        return $key === null ? $decoded : ($decoded[$key] ?? $default);
    }

    /**
     * Get the bearer token from the Authorization header.
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    /**
     * Get the route parameters from the current route.
     */
    public function routeParameters(): array
    {
        $route = $this->route();
        return $route ? $route->getParameters() : [];
    }

    /**
     * Get the current route.
     */
    public function route(): mixed
    {
        return $this->routeResolver ? ($this->routeResolver)() : null;
    }

    /**
     * Set the route resolver.
     */
    public function setRouteResolver(Closure $resolver): void
    {
        $this->routeResolver = $resolver;
    }

    /**
     * Get the client IP address.
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['HTTP_CLIENT_IP']
            ?? $this->server['REMOTE_ADDR']
            ?? '127.0.0.1';
    }

    /**
     * Get the request host.
     */
    public function host(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get full URL.
     */
    public function url(): string
    {
        $scheme = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        return "{$scheme}://{$this->host()}{$this->uri()}";
    }

    /**
     * Validate request data.
     */
    public function validate(array $rules): array
    {
        $validator = new \Papastro\Validation\Validator($this->all(), $rules);

        if ($validator->fails()) {
            throw new \Papastro\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
