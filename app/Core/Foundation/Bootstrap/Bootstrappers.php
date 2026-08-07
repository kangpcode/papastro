<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.1.0
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
            $name  = trim($name);
            $value = trim($value);

            // Strip surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($name, $_ENV)) {
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
        $config = new \Papastro\Config\Repository($app->configPath());
        $app->instance('config', $config);
        $app->instance(\Papastro\Config\Repository::class, $config);

        // Set PHP timezone from config
        date_default_timezone_set(
            $config->get('app.timezone', 'UTC')
        );
    }
}

/**
 * Configure the exception handling for the application.
 */
class HandleExceptions
{
    protected Application $app;

    public function bootstrap(Application $app): void
    {
        $this->app = $app;

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
        if ($this->app->debug()) {
            $this->renderDebugException($e);
        } else {
            error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            if (!headers_sent()) {
            @http_response_code(500);
        }
            echo 'Internal Server Error';
        }
    }

    protected function renderDebugException(\Throwable $e): void
    {
        if (!headers_sent()) {
            @http_response_code(500);
        }
        $message = htmlspecialchars($e->getMessage());
        $file    = htmlspecialchars($e->getFile());
        $line    = $e->getLine();
        $trace   = htmlspecialchars($e->getTraceAsString());

        echo <<<HTML
<!DOCTYPE html><html><head><title>Error</title>
<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:40px;}
h1{color:#f87171;} pre{background:#1e293b;padding:20px;border-radius:8px;overflow:auto;}</style>
</head><body>
<h1>{$message}</h1>
<p>in <strong>{$file}</strong> on line <strong>{$line}</strong></p>
<pre>{$trace}</pre>
</body></html>
HTML;
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
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
