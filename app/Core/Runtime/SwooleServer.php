<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Runtime;

use Papastro\Foundation\Application;
use Papastro\Http\Request;
use Papastro\Http\Response;

/**
 * SwooleServer — Swoole Coroutine HTTP Server adapter for ultra-high throughput (100k+ req/s).
 */
class SwooleServer
{
    protected Application $app;
    protected string $host;
    protected int $port;
    protected int $workers;
    protected int $maxRequests;
    protected bool $watch;

    public function __construct(
        Application $app,
        string $host = '127.0.0.1',
        int $port = 8000,
        int $workers = 0,
        int $maxRequests = 10000,
        bool $watch = false
    ) {
        $this->app = $app;
        $this->host = $host;
        $this->port = $port;
        $this->workers = $workers > 0 ? $workers : (function_exists('swoole_cpu_num') ? swoole_cpu_num() : 4);
        $this->maxRequests = $maxRequests;
        $this->watch = $watch;
    }

    /**
     * Start the Swoole HTTP server.
     */
    public function start(): void
    {
        if (!class_exists('\Swoole\Http\Server')) {
            throw new \RuntimeException("ext-swoole is required to use the Swoole runtime driver.");
        }

        $server = new \Swoole\Http\Server($this->host, $this->port);

        $server->set([
            'worker_num'               => $this->workers,
            'max_request'              => $this->maxRequests,
            'enable_coroutine'         => true,
            'send_yield'               => true,
            'open_tcp_nodelay'         => true,
            'max_coroutine'            => 100000,
            'buffer_output_size'       => 32 * 1024 * 1024,
            'package_max_length'       => 20 * 1024 * 1024,
            'reload_async'             => true,
            'max_wait_time'            => 10,
            'enable_static_handler'    => true,
            'document_root'            => $this->app->publicPath(),
        ]);

        $server->on('Start', function ($server) {
            echo "\033[32m";
            echo "  [Swoole] Papastro Coroutine Server (v4.0.0)\n";
            echo "  @author kangpcode (Dhafa Nazula Permadi)\n";
            echo "\033[0m";
            echo "  - Listening on: http://{$this->host}:{$this->port}\n";
            echo "  - Workers     : {$this->workers} Swoole coroutine workers\n";
            echo "  Press Ctrl+C to stop.\n\n";

            $pidFile = $this->app->storagePath('framework/runtime.pid');
            @file_put_contents($pidFile, (string) $server->master_pid);
        });

        $server->on('Request', function ($swooleReq, $swooleRes) {
            $kernel = $this->app->make(\Papastro\Http\Kernel::class);

            $headers = $swooleReq->header ?? [];
            $serverParams = [];
            foreach ($swooleReq->server ?? [] as $k => $v) {
                $serverParams[strtoupper($k)] = (string) $v;
            }
            foreach ($headers as $k => $v) {
                $serverParams['HTTP_' . strtoupper(str_replace('-', '_', $k))] = (string) $v;
            }

            $rawBody = (string) $swooleReq->rawContent();
            $method = (string) ($swooleReq->server['request_method'] ?? 'GET');
            $path = (string) ($swooleReq->server['request_uri'] ?? '/');

            $request = new Request(
                $method,
                $path,
                $swooleReq->get ?? [],
                $swooleReq->post ?? [],
                $serverParams,
                $swooleReq->files ?? [],
                $swooleReq->cookie ?? [],
                $headers
            );
            $request->setRawBody($rawBody);

            $this->app->instance('request', $request);

            try {
                $response = $kernel->handle($request);
            } catch (\Throwable $e) {
                $response = Response::html("<h1>500 Internal Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>", 500);
            }

            $swooleRes->status($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $value) {
                $swooleRes->header($name, $value);
            }

            foreach ($response->getCookies() as $cKey => $cookie) {
                $cName = $cookie['name'] ?? (is_string($cKey) ? $cKey : '');
                if ($cName === '') {
                    continue;
                }
                $expires = $cookie['expires'] ?? (isset($cookie['minutes']) ? time() + $cookie['minutes'] * 60 : 0);
                $swooleRes->cookie(
                    $cName,
                    $cookie['value'] ?? '',
                    $expires,
                    $cookie['path'] ?? '/',
                    $cookie['domain'] ?? '',
                    $cookie['secure'] ?? false,
                    $cookie['httpOnly'] ?? true,
                    $cookie['sameSite'] ?? 'lax'
                );
            }

            $swooleRes->end((string) $response->getContent());

            $kernel->terminate($request, $response);
            StateResetter::reset($this->app);
        });

        $server->start();
    }
}
