<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Foundation\Bootstrap;

use Papastro\Foundation\Application;

/**
 * Load environment variables from .env file.
 */
class LoadEnvironmentVariables
{
    public function bootstrap(Application $app): void
    {
        $envFile = $app->basePath('.env');

        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Strip surrounding quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($name, $_ENV) && getenv($name) === false) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}

/**
 * Load the application configuration from the config directory.
 */
class LoadConfiguration
{
    public function bootstrap(Application $app): void
    {
        $config = new \Papastro\Config\Repository($app->configPath(), $app->storagePath('cache/config/config.php'));
        $app->instance('config', $config);
        $app->instance(\Papastro\Config\Repository::class, $config);

        // Set PHP timezone from config
        date_default_timezone_set(
            (string) $config->get('app.timezone', 'UTC')
        );

        // Only these proxies may supply X-Forwarded-* headers (prevents IP spoofing).
        \Papastro\Http\Request::setTrustedProxies((array) $config->get('app.trusted_proxies', []));
    }
}

/**
 * Configure the exception handling for the application.
 */
class HandleExceptions
{
    protected Application $app;
    protected static ?string $reservedMemory = null;

    public function bootstrap(Application $app): void
    {
        $this->app = $app;

        // Reserve 32KB of memory to guarantee graceful crash handling on Out-Of-Memory
        static::$reservedMemory = str_repeat(' ', 32768);

        error_reporting(E_ALL);

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    public function handleException(\Throwable $e): void
    {
        $isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['REQUEST_URI']) && str_starts_with(ltrim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), 'api/'));

        if ($this->app->debug()) {
            if ($isCli) {
                fwrite(STDERR, "\n\033[31;1m[" . get_class($e) . "]\033[0m " . $e->getMessage() . "\n");
                fwrite(STDERR, "in \033[33m" . $e->getFile() . "\033[0m on line \033[33m" . $e->getLine() . "\033[0m\n\n");
                fwrite(STDERR, $e->getTraceAsString() . "\n\n");
                return;
            }

            if ($isJson) {
                if (!headers_sent()) {
                    @http_response_code(500);
                    header('Content-Type: application/json; charset=UTF-8');
                }
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                return;
            }

            $this->renderDebugException($e);
        } else {
            error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            if ($isCli) {
                fwrite(STDERR, "\n\033[31mInternal Server Error\033[0m\n");
                return;
            }

            if (!headers_sent()) {
                @http_response_code(500);
            }

            if ($isJson) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['status' => 'error', 'message' => 'Internal Server Error']);
                return;
            }

            echo 'Internal Server Error';
        }
    }

    protected function renderDebugException(\Throwable $e): void
    {
        if (!headers_sent()) {
            @http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        $message = htmlspecialchars($e->getMessage());
        $class = htmlspecialchars(get_class($e));
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString());

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Papastro — Exception</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; background: #0f172a; color: #e2e8f0; padding: 40px; }
        .box { max-width: 1000px; margin: 0 auto; background: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .banner { background: #dc2626; padding: 20px 24px; }
        .banner h1 { font-size: 13px; text-transform: uppercase; letter-spacing: 0.1em; color: #fca5a5; margin-bottom: 6px; }
        .banner h2 { font-size: 20px; color: #fff; font-weight: 700; word-break: break-word; }
        .meta { padding: 16px 24px; background: #182234; border-bottom: 1px solid #334155; font-size: 13px; color: #94a3b8; }
        .meta strong { color: #f8fafc; }
        .content { padding: 24px; }
        pre { background: #0b1120; padding: 18px; border-radius: 8px; font-size: 12px; line-height: 1.6; color: #cbd5e1; overflow-x: auto; border: 1px solid #1e293b; }
    </style>
</head>
<body>
    <div class="box">
        <div class="banner">
            <h1>Papastro Framework v4.0.0 — Unhandled Exception</h1>
            <h2>{$message}</h2>
        </div>
        <div class="meta">
            <span>Thrown in <strong>{$file}</strong> on line <strong>{$line}</strong> [{$class}]</span>
        </div>
        <div class="content">
            <pre>{$trace}</pre>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public function handleShutdown(): void
    {
        // Free reserved memory buffer so fatal OOM can still report gracefully
        static::$reservedMemory = null;

        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }
}

/**
 * Register all configured service providers.
 */
class RegisterProviders
{
    public function bootstrap(Application $app): void
    {
        $providers = $app->make('config')->get('app.providers', []);

        foreach ($providers as $provider) {
            $app->register($provider);
        }
    }
}

/**
 * Boot all registered service providers.
 */
class BootProviders
{
    public function bootstrap(Application $app): void
    {
        $app->boot();
    }
}
