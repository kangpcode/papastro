<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Http\Rpc;

use Papastro\Foundation\Application;
use Papastro\Http\Request;
use Papastro\Http\Response;
use ReflectionMethod;
use Throwable;

/**
 * PasRPC — End-to-end typed Remote Procedure Call gateway for Astro Islands (.pstro) and client APIs.
 * Eliminates REST & GraphQL boilerplate with direct JSON procedure dispatch.
 */
class RpcGateway
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Dispatch an RPC call to a controller action with typed argument resolution.
     */
    public function dispatch(Request $request, string $controllerClass, ?string $method = null): Response
    {
        // Serve client JavaScript runtime if requested
        if ($request->query('client') !== null || $request->header('Accept') === 'application/javascript') {
            $version = Application::getFrameworkVersion();
            $path = $request->path();
            $js = <<<JS
/**
 * Papastro PasRPC Client Runtime v{$version}
 * @author kangpcode (Dhafa Nazula Permadi)
 */
(function(window) {
    'use strict';
    window.__PAPASTRO__ = window.__PAPASTRO__ || {
        version: "{$version}",
        framework: "Papastro",
        creator: "kangpcode (Dhafa Nazula Permadi)",
        website: "https://kangpcode.github.io/papastro"
    };
    window.Papastro = window.Papastro || {};
    window.Papastro.version = "{$version}";
    window.pasrpc = async function(method, params = {}) {
        const res = await fetch("{$path}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ method, params })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error?.message || 'RPC Error');
        return data.result;
    };
})(window);
JS;
            return Response::make($js, 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
        }

        $payload = $request->json();
        $targetMethod = $method ?: ($payload['method'] ?? $request->query('method', 'index'));
        $params = $payload['params'] ?? $payload['data'] ?? $payload;

        if (!is_array($params)) {
            $params = [];
        }

        if (!class_exists($controllerClass)) {
            return Response::json([
                'success' => false,
                'error'   => ['code' => 404, 'message' => "RPC Controller [{$controllerClass}] not found."],
            ], 404);
        }

        $controller = $this->app->make($controllerClass);

        if (!method_exists($controller, $targetMethod) || !is_callable([$controller, $targetMethod])) {
            return Response::json([
                'success' => false,
                'error'   => ['code' => 404, 'message' => "RPC Method [{$targetMethod}] not found on [{$controllerClass}]."],
            ], 404);
        }

        try {
            $refMethod = new ReflectionMethod($controller, $targetMethod);
            $args = [];

            foreach ($refMethod->getParameters() as $refParam) {
                $paramName = $refParam->getName();
                $paramType = $refParam->getType();

                if ($paramType && method_exists($paramType, 'isBuiltin') && !$paramType->isBuiltin()) {
                    $typeName = (string) $paramType->getName();
                    if ($typeName === Request::class || is_subclass_of($typeName, Request::class)) {
                        $args[] = $request;
                        continue;
                    }
                    if ($this->app->bound($typeName)) {
                        $args[] = $this->app->make($typeName);
                        continue;
                    }
                }

                if (array_key_exists($paramName, $params)) {
                    $val = $params[$paramName];
                    if ($paramType && method_exists($paramType, 'isBuiltin') && $paramType->isBuiltin()) {
                        settype($val, $paramType->getName());
                    }
                    $args[] = $val;
                } elseif ($refParam->isDefaultValueAvailable()) {
                    $args[] = $refParam->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }

            $result = $refMethod->invokeArgs($controller, $args);

            if ($result instanceof Response) {
                return $result;
            }

            return Response::json([
                'success' => true,
                'result'  => $result,
            ], 200);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'error'   => [
                    'code'    => 500,
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Generate inline JavaScript runtime for PasRPC and browser client APIs.
     * Exposes window.__PAPASTRO__ = { version: "..." } and window.pasrpc.
     */
    public static function clientScript(string $endpoint = '/api/rpc'): string
    {
        $version = Application::getFrameworkVersion();
        $expose = Application::shouldExposeFramework() ? 'true' : 'false';

        return <<<HTML
<script>
(function(window) {
    'use strict';
    const version = "{$version}";
    const shouldExpose = {$expose};
    if (shouldExpose) {
        window.__PAPASTRO__ = window.__PAPASTRO__ || {
            version: version,
            framework: "Papastro",
            creator: "kangpcode (Dhafa Nazula Permadi)",
            website: "https://kangpcode.github.io/papastro"
        };
        window.Papastro = window.Papastro || {};
        window.Papastro.version = version;
    }
    window.pasrpc = async function(method, params = {}) {
        const res = await fetch("{$endpoint}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ method, params })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error?.message || 'RPC Error');
        return data.result;
    };
})(window);
</script>
HTML;
    }
}

