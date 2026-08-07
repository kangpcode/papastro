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

/**
 * Global helper functions for the Papastro Framework.
 */

if (!function_exists('app')) {
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $instance = \Papastro\Foundation\Application::getInstance();

        if ($abstract === null) {
            return $instance;
        }

        return $instance->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $repo = app('config');

        if ($key === null) {
            return $repo;
        }

        return $repo->get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return app()->appPath($path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return app()->storagePath($path);
    }
}

if (!function_exists('resource_path')) {
    function resource_path(string $path = ''): string
    {
        return app()->resourcePath($path);
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return app()->publicPath($path);
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return app()->configPath($path);
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return app()->databasePath($path);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): \Papastro\Http\Response
    {
        return \Papastro\Http\Response::redirect($url, $status);
    }
}

if (!function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): \Papastro\Http\Response
    {
        return \Papastro\Http\Response::make($content, $status, $headers);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): \Papastro\Http\Response
    {
        $engine  = app(\Papastro\View\ViewEngine::class);
        $content = $engine->render($template, $data);
        return \Papastro\Http\Response::make($content);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $parameters = []): string
    {
        return app('router')->route($name, $parameters);
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        throw new \Papastro\Http\Exception\HttpException($code, $message);
    }
}

if (!function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        $req = app('request');
        if ($key === null) return $req;
        return $req->input($key, $default);
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if ($key === null) {
            return $_SESSION;
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . htmlspecialchars(strtoupper($method)) . '">';
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('slug')) {
    function slug(string $text): string
    {
        $text = preg_replace('/[^\w\s-]/u', '', mb_strtolower($text));
        $text = preg_replace('/[\s_]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('now')) {
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('str_contains_any')) {
    function str_contains_any(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) return true;
        }
        return false;
    }
}

if (!function_exists('event')) {
    function event(object|string $event, array $payload = []): array
    {
        return app(\Papastro\Events\EventDispatcher::class)->dispatch($event, $payload);
    }
}

if (!function_exists('flash')) {
    function flash(?string $key = null, mixed $value = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if ($key === null) {
            $flashes = $_SESSION['_flash'] ?? [];
            $_SESSION['_flash'] = [];
            return $flashes;
        }

        if ($value === null) {
            $msg = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }

        $_SESSION['_flash'][$key] = $value;
        return $value;
    }
}
