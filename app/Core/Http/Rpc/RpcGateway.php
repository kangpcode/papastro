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
}

