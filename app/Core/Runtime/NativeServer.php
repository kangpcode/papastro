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
 * NativeServer — High-concurrency pre-forking HTTP server with zero external dependencies.
 * Uses Linux SO_REUSEPORT, stream_socket_server, and pcntl worker management.
 */
class NativeServer
{
    protected Application $app;
    protected string $host;
    protected int $port;
    protected int $workers;
    protected int $maxRequests;
    protected int $maxMemoryMB;
    protected bool $watch;
    protected ?string $pidFile;

    /** @var array<int, int> Worker PIDs [pid => pid] */
    protected array $workerPids = [];

    protected bool $running = false;

    public function __construct(
        Application $app,
        string $host = '127.0.0.1',
        int $port = 8000,
        int $workers = 0,
        int $maxRequests = 10000,
        int $maxMemoryMB = 128,
        bool $watch = false
    ) {
        $this->app = $app;
        $this->host = $host;
        $this->port = $port;
        $this->workers = $workers > 0 ? $workers : $this->detectCpuCount();
        $this->maxRequests = $maxRequests;
        $this->maxMemoryMB = $maxMemoryMB;
        $this->watch = $watch;
        $this->pidFile = $app->storagePath('framework/runtime.pid');
    }

    /**
     * Start the master server process.
     */
    public function start(): void
    {
        $uri = "tcp://{$this->host}:{$this->port}";

        // Write PID file
        $pidDir = dirname($this->pidFile);
        if (!is_dir($pidDir)) {
            @mkdir($pidDir, 0755, true);
        }
        @file_put_contents($this->pidFile, (string) getmypid());

        $this->running = true;

        echo "\033[36m";
        echo "  🚀 Papastro Octane-Style Runtime Server (v3.0.0)\n";
        echo "  @author kangpcode (Dhafa Nazula Permadi)\n";
        echo "\033[0m";
        echo "  ⚡ Host & Port : http://{$this->host}:{$this->port}\n";
        echo "  👷 Workers     : {$this->workers} pre-forked workers\n";
        echo "  🔄 Max Requests: {$this->maxRequests} reqs/worker\n";
        echo "  👀 Hot Watch   : " . ($this->watch ? "\033[32mEnabled\033[0m" : "Disabled") . "\n";
        echo "  Server PID     : " . getmypid() . "\n";
        echo "  Press Ctrl+C to stop.\n\n";

        // Register master signal handlers
        $this->registerMasterSignals();

        // Fork initial workers
        for ($i = 0; $i < $this->workers; $i++) {
            $this->forkWorker();
        }

        $watcher = $this->watch ? new FileWatcher([
            $this->app->basePath('app'),
            $this->app->basePath('config'),
            $this->app->basePath('routes'),
            $this->app->basePath('.env'),
        ]) : null;

        $lastWatchCheck = time();

        // Master loop: monitor workers and restart on exit or file changes
        while ($this->running) {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            // Check if any worker has exited
            while (($exitedPid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
                unset($this->workerPids[$exitedPid]);
                if ($this->running) {
                    $this->forkWorker();
                }
            }

            // Check file watcher every 1 second
            if ($watcher && (time() - $lastWatchCheck >= 1)) {
                $lastWatchCheck = time();
                if ($watcher->hasChanged()) {
                    echo "\033[33m[Papastro Runtime] Code change detected. Reloading workers...\033[0m\n";
                    $this->reloadWorkers();
                }
            }

            usleep(100000); // 100ms sleep
        }

        $this->cleanup();
    }

    /**
     * Fork a child worker process.
     */
    protected function forkWorker(): void
    {
        if (!function_exists('pcntl_fork')) {
            // Fallback: single process accept loop
            $this->runWorkerLoop();
            return;
        }

        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new \RuntimeException("Failed to fork worker process.");
        } elseif ($pid === 0) {
            // Child process: set up worker and enter loop
            $this->runWorkerLoop();
            exit(0);
        } else {
            // Master process: record PID
            $this->workerPids[$pid] = $pid;
        }
    }

    /**
     * The worker process execution loop.
     */
    protected function runWorkerLoop(): void
    {
        // Child handles SIGTERM gracefully
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, function () {
                exit(0);
            });
            pcntl_signal(SIGINT, function () {
                exit(0);
            });
        }

        $context = stream_context_create([
            'socket' => [
                'so_reuseport' => 1,
                'so_reuseaddr' => 1,
                'backlog'      => 4096,
            ],
        ]);

        $server = @stream_socket_server(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        );

        if (!$server) {
            return;
        }

        stream_set_blocking($server, true);

        $kernel = $this->app->make(\Papastro\Http\Kernel::class);
        $requestsHandled = 0;

        while (true) {
            $conn = @stream_socket_accept($server, 1.0);
            if (!$conn) {
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
                continue;
            }

            stream_set_timeout($conn, 5);

            // Read raw HTTP request
            $raw = '';
            while (!str_contains($raw, "\r\n\r\n")) {
                $buf = @fread($conn, 4096);
                if ($buf === false || $buf === '') {
                    break;
                }
                $raw .= $buf;
            }

            if (empty($raw)) {
                @fclose($conn);
                continue;
            }

            [$head, $initialBody] = explode("\r\n\r\n", $raw, 2);
            $lines = explode("\r\n", $head);
            $requestLine = array_shift($lines);
            [$method, $target] = array_pad(explode(' ', $requestLine, 3), 2, '/');

            $headers = [];
            foreach ($lines as $line) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $headers[strtolower(trim($k))] = trim($v);
                }
            }

            // Read full body if Content-Length is present
            $body = $initialBody;
            $contentLength = (int) ($headers['content-length'] ?? 0);
            while (strlen($body) < $contentLength) {
                $buf = @fread($conn, 8192);
                if ($buf === false || $buf === '') break;
                $body .= $buf;
            }

            // Construct Papastro Request
            $parsedUrl = parse_url($target);
            $path = $parsedUrl['path'] ?? '/';
            $queryString = $parsedUrl['query'] ?? '';
            parse_str($queryString, $queryParams);

            $parsedBody = [];
            $contentType = $headers['content-type'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $parsedBody = json_decode($body, true) ?: [];
            } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                parse_str($body, $parsedBody);
            }

            $cookies = [];
            if (!empty($headers['cookie'])) {
                $cookiePairs = explode(';', $headers['cookie']);
                foreach ($cookiePairs as $pair) {
                    if (str_contains($pair, '=')) {
                        [$ck, $cv] = explode('=', trim($pair), 2);
                        $cookies[trim($ck)] = urldecode(trim($cv));
                    }
                }
            }

            $serverParams = [
                'REQUEST_METHOD'  => strtoupper($method),
                'REQUEST_URI'     => $target,
                'PATH_INFO'       => $path,
                'QUERY_STRING'    => $queryString,
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'SERVER_NAME'     => $this->host,
                'SERVER_PORT'     => (string) $this->port,
                'REMOTE_ADDR'     => stream_socket_get_name($conn, true) ?: '127.0.0.1',
                'HTTP_HOST'       => $headers['host'] ?? "{$this->host}:{$this->port}",
            ];

            foreach ($headers as $hk => $hv) {
                $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $hk));
                $serverParams[$serverKey] = $hv;
            }

            $request = new Request($method, $path, $queryParams, $parsedBody, $serverParams, [], $cookies, $headers);
            $request->setRawBody($body);
            $this->app->instance('request', $request);

            try {
                $response = $kernel->handle($request);
            } catch (\Throwable $e) {
                $response = Response::html(
                    "<h1>500 Internal Server Error</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>",
                    500
                );
            }

            // Write HTTP Response
            $statusCode = $response->getStatusCode();
            $statusText = $this->getStatusText($statusCode);
            $responseBody = (string) $response->getContent();

            $resHeaders = $response->getHeaders();
            $resHeaders['Content-Length'] = (string) strlen($responseBody);
            $resHeaders['Connection'] = 'close';
            $resHeaders['Server'] = 'Papastro-Octane/3.0.0';

            $out = "HTTP/1.1 {$statusCode} {$statusText}\r\n";
            foreach ($resHeaders as $hk => $hv) {
                $out .= "{$hk}: {$hv}\r\n";
            }

            // Send cookies
            foreach ($response->getCookies() as $cookie) {
                $out .= "Set-Cookie: {$cookie['name']}=" . urlencode($cookie['value']);
                if (!empty($cookie['expires'])) {
                    $out .= "; Expires=" . gmdate('D, d M Y H:i:s T', $cookie['expires']);
                }
                if (!empty($cookie['path'])) {
                    $out .= "; Path={$cookie['path']}";
                }
                if (!empty($cookie['domain'])) {
                    $out .= "; Domain={$cookie['domain']}";
                }
                if (!empty($cookie['secure'])) {
                    $out .= "; Secure";
                }
                if (!empty($cookie['httpOnly'])) {
                    $out .= "; HttpOnly";
                }
                if (!empty($cookie['sameSite'])) {
                    $out .= "; SameSite={$cookie['sameSite']}";
                }
                $out .= "\r\n";
            }

            $out .= "\r\n" . $responseBody;
            @fwrite($conn, $out);
            @fclose($conn);

            // Request lifecycle completion
            $kernel->terminate($request, $response);
            StateResetter::reset($this->app);

            $requestsHandled++;

            // Recycle worker if limits reached
            if ($requestsHandled >= $this->maxRequests) {
                break;
            }

            if (memory_get_usage(true) > ($this->maxMemoryMB * 1024 * 1024)) {
                break;
            }
        }

        @fclose($server);
        exit(0);
    }

    /**
     * Gracefully reload all workers with zero downtime.
     */
    public function reloadWorkers(): void
    {
        $oldPids = $this->workerPids;
        $this->workerPids = [];

        // Fork new generation of workers first
        for ($i = 0; $i < $this->workers; $i++) {
            $this->forkWorker();
        }

        // Send SIGTERM to old generation workers
        foreach ($oldPids as $pid) {
            @posix_kill($pid, SIGTERM);
        }
    }

    /**
     * Clean up processes and PID file on shutdown.
     */
    protected function cleanup(): void
    {
        foreach ($this->workerPids as $pid) {
            @posix_kill($pid, SIGTERM);
        }

        if ($this->pidFile && file_exists($this->pidFile)) {
            @unlink($this->pidFile);
        }
    }

    /**
     * Register POSIX signals for master process.
     */
    protected function registerMasterSignals(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_signal(SIGTERM, function () {
            $this->running = false;
        });
        pcntl_signal(SIGINT, function () {
            $this->running = false;
        });
        pcntl_signal(SIGUSR1, function () {
            $this->reloadWorkers();
        });
        pcntl_signal(SIGHUP, function () {
            $this->reloadWorkers();
        });
    }

    /**
     * Automatically detect CPU cores on Linux.
     */
    protected function detectCpuCount(): int
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $count = count($matches[0]);
            if ($count > 0) return $count;
        }

        return 4;
    }

    /**
     * Standard HTTP status texts.
     */
    protected function getStatusText(int $code): string
    {
        $texts = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];

        return $texts[$code] ?? 'Unknown';
    }
}
