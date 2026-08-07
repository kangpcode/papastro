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

namespace Papastro\Http;

use Papastro\Foundation\Application;
use Throwable;

/**
 * HTTP Kernel — handles incoming requests through the middleware pipeline.
 */
class Kernel
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The router instance.
     */
    protected \Papastro\Router\Router $router;

    /**
     * The application's global middleware stack.
     */
    protected array $middleware = [];

    /**
     * The application's middleware groups.
     */
    protected array $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    /**
     * The application's route middleware.
     */
    protected array $routeMiddleware = [];

    /**
     * Create a new HTTP Kernel instance.
     */
    public function __construct(Application $app, \Papastro\Router\Router $router)
    {
        $this->app    = $app;
        $this->router = $router;
    }

    /**
     * Handle an incoming HTTP request.
     */
    public function handle(Request $request): Response
    {
        try {
            $response = $this->sendRequestThroughRouter($request);
        } catch (Exception\HttpException $e) {
            $response = $this->renderHttpException($e);
        } catch (Throwable $e) {
            $response = $this->renderException($request, $e);
        }

        return $response;
    }

    /**
     * Send the request through the router.
     */
    protected function sendRequestThroughRouter(Request $request): Response
    {
        $this->app->instance('request', $request);

        $this->bootstrap();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($this->middleware)
            ->then(fn ($req) => $this->router->dispatch($req));
    }

    /**
     * Bootstrap the application for HTTP handling.
     */
    protected function bootstrap(): void
    {
        if (!$this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }
    }

    /**
     * Get the bootstrap classes for the application.
     */
    protected function bootstrappers(): array
    {
        return [
            \Papastro\Foundation\Bootstrap\LoadEnvironmentVariables::class,
            \Papastro\Foundation\Bootstrap\LoadConfiguration::class,
            \Papastro\Foundation\Bootstrap\HandleExceptions::class,
            \Papastro\Foundation\Bootstrap\RegisterProviders::class,
            \Papastro\Foundation\Bootstrap\BootProviders::class,
        ];
    }

    /**
     * Render an HTTP exception into a response.
     */
    protected function renderHttpException(Exception\HttpException $e): Response
    {
        return Response::make($e->getMessage(), $e->getStatusCode());
    }

    /**
     * Render a generic exception into a response.
     */
    protected function renderException(Request $request, Throwable $e): Response
    {
        if ($this->app->debug()) {
            return $this->renderDebugException($e);
        }

        return Response::make('Internal Server Error', 500);
    }

    /**
     * Render a debug-mode exception with full stack trace.
     */
    protected function renderDebugException(Throwable $e): Response
    {
        $message = htmlspecialchars($e->getMessage());
        $class   = get_class($e);
        $file    = htmlspecialchars($e->getFile());
        $line    = $e->getLine();
        $trace   = htmlspecialchars($e->getTraceAsString());

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papastro — Exception</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'JetBrains Mono', monospace; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        .header { background: #dc2626; padding: 20px 40px; }
        .header h1 { font-size: 14px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; opacity: 0.7; }
        .header h2 { font-size: 24px; font-weight: 700; margin-top: 4px; }
        .header .class { font-size: 13px; opacity: 0.7; margin-top: 6px; font-family: monospace; }
        .location { background: #1e293b; padding: 16px 40px; border-bottom: 1px solid #334155; }
        .location span { color: #94a3b8; font-size: 13px; }
        .location strong { color: #f8fafc; }
        .body { padding: 40px; }
        .section-title { font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; margin-bottom: 12px; }
        pre { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 20px; font-size: 12px; line-height: 1.7; overflow-x: auto; color: #94a3b8; }
        .badge { display: inline-block; background: #dc2626; color: white; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; }
        footer { padding: 20px 40px; border-top: 1px solid #1e293b; font-size: 12px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌟 Papastro Framework — Unhandled Exception</h1>
        <h2>{$message}</h2>
        <div class="class">{$class}</div>
    </div>
    <div class="location">
        <span>Thrown in </span><strong>{$file}</strong><span> on line </span><strong>{$line}</strong>
    </div>
    <div class="body">
        <div class="section-title">Stack Trace</div>
        <pre>{$trace}</pre>
    </div>
    <footer>Papastro Framework v{$this->app->version()} &middot; <span class="badge">DEBUG MODE</span></footer>
</body>
</html>
HTML;

        return Response::make($html, 500);
    }

    /**
     * Perform post-response tasks.
     */
    public function terminate(Request $request, Response $response): void
    {
        foreach ($this->middleware as $middleware) {
            if (!is_string($middleware)) continue;

            $instance = $this->app->make($middleware);

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }

    /**
     * Get the application's middleware stack.
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the application's middleware groups.
     */
    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }
}
