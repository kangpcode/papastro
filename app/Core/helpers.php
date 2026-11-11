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
        $app = \Papastro\Foundation\Application::getInstance();
        if ($app === null || !$app->resolved('config')) {
            return $default;
        }

        $repo = $app->make('config');

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
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
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
        $engine = app(\Papastro\View\ViewEngine::class);
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
        if ($key === null)
            return $req;
        return $req->input($key, $default);
    }
}

if (!function_exists('session')) {
    /**
     * Access the current request session (set up by StartSession middleware).
     * session()            → Session instance
     * session('key')       → get
     * session(['k' => 'v'])→ put
     */
    function session(string|array|null $key = null, mixed $default = null): mixed
    {
        $app = \Papastro\Foundation\Application::getInstance();
        if (!$app->bound('session')) {
            $store = new \Papastro\Session\Session(storage_path('sessions'));
            $store->start();
            $app->instance('session', $store);
            $app->instance(\Papastro\Session\Session::class, $store);
        }
        $session = $app->make('session');

        if ($key === null) {
            return $session;
        }
        if (is_array($key)) {
            $session->put($key);
            return null;
        }
        return $session->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
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
            if (str_contains($haystack, $needle))
                return true;
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
    /**
     * flash()           → pull all flash data
     * flash('k')        → pull one value
     * flash('k', 'v')   → flash a value for the next request
     */
    function flash(?string $key = null, mixed $value = null): mixed
    {
        $session = session();
        if ($key === null) {
            return $session->pullAllFlash();
        }
        if ($value === null) {
            return $session->pullFlash($key);
        }
        $session->flash($key, $value);
        return $value;
    }
}

if (!function_exists('cache')) {
    function cache(?string $key = null, mixed $default = null): mixed
    {
        $cache = app(\Papastro\Cache\CacheManager::class);
        return $key === null ? $cache : $cache->get($key, $default);
    }
}

if (!function_exists('logger')) {
    function logger(?string $message = null, array $context = []): mixed
    {
        $log = app(\Papastro\Log\Logger::class);
        if ($message === null) {
            return $log;
        }
        $log->debug($message, $context);
        return null;
    }
}

if (!function_exists('value')) {
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('dispatch')) {
    function dispatch(object $job, string $queue = 'default'): string
    {
        return app(\Papastro\Queue\QueueManager::class)->dispatch($job, $queue);
    }
}

if (!function_exists('broadcast')) {
    /**
     * Broadcast a realtime event over Pasway (WebSocket/SSE).
     * broadcast('orders', 'order.shipped', ['id' => 1]);
     */
    function broadcast(string|array $channels, string $event, array $data = []): bool
    {
        return \Papastro\Pasway\Pasway::broadcast($channels, $event, $data);
    }
}

if (!function_exists('pastroutine')) {
    /**
     * Spawn a Pastroutine (lightweight cooperative concurrent task).
     * Named pastroutine() — not go() — because ext-swoole already owns a global go().
     */
    function pastroutine(callable $task, mixed ...$args): \Papastro\Pastroutine\Pastroutine
    {
        return \Papastro\Pastroutine\Scheduler::current()->go($task, ...$args);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        $old = session()->get('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    function errors(?string $key = null): mixed
    {
        $errors = session()->get('_errors', []);
        return $key === null ? $errors : ($errors[$key] ?? []);
    }
}

if (!function_exists('bcrypt')) {
    function bcrypt(string $value, array $options = []): string
    {
        return \Papastro\Security\Hash::make($value, $options);
    }
}

if (!function_exists('encrypt')) {
    function encrypt(mixed $value, bool $serialize = true): string
    {
        return app(\Papastro\Security\Encrypter::class)->encrypt($value, $serialize);
    }
}

if (!function_exists('decrypt')) {
    function decrypt(string $payload, bool $unserialize = true): mixed
    {
        return app(\Papastro\Security\Encrypter::class)->decrypt($payload, $unserialize);
    }
}

if (!function_exists('pascon')) {
    /**
     * Render a Pascon native SVG icon.
     *
     * @param string $name
     * @param array $attributes
     * @return string
     */
    function pascon(string $name, array $attributes = []): string
    {
        return \Papastro\Pascon\Pascon::svg($name, $attributes);
    }
}
