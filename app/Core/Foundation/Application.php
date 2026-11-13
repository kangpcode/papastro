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

namespace Papastro\Foundation;

use Papastro\Container\Container;

/**
 * The Papastro Application — heart of the framework.
 * Acts as both Service Container and Application bootstrapper.
 */
class Application extends Container
{
    /**
     * The Papastro framework version.
     */
    public const VERSION = '4.0.0';

    /**
     * The base path for the Papastro installation.
     */
    protected string $basePath = '';

    /**
     * Indicates if the application has been bootstrapped.
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * Indicates if the application has booted.
     */
    protected bool $booted = false;

    /**
     * All registered service providers.
     */
    protected array $serviceProviders = [];

    /**
     * The loaded service providers.
     */
    protected array $loadedProviders = [];

    /**
     * Deferred provider services.
     */
    protected array $deferredServices = [];

    /**
     * The registered aliases.
     */
    protected array $aliases = [];

    /**
     * Create a new Papastro Application instance.
     */
    public function __construct(string $basePath = '')
    {
        $this->setBasePath($basePath ?: dirname(__DIR__, 3));

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * Get the version number of the application.
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Set the base path for the application.
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Get the base path of the Papastro installation.
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Get the path to the application directory.
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath('app' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the public directory.
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the storage directory.
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the resources directory.
     */
    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the config directory.
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the database directory.
     */
    public function databasePath(string $path = ''): string
    {
        return $this->basePath('database' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the plugins directory.
     */
    public function pluginPath(string $path = ''): string
    {
        return $this->basePath('plugins' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Bind all of the application paths in the container.
     */
    protected function bindPathsInContainer(): void
    {
        $this->instance('path',          $this->appPath());
        $this->instance('path.base',     $this->basePath());
        $this->instance('path.config',   $this->configPath());
        $this->instance('path.public',   $this->publicPath());
        $this->instance('path.storage',  $this->storagePath());
        $this->instance('path.resource', $this->resourcePath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.plugin',   $this->pluginPath());
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Application::class, $this);
        $this->instance(Container::class, $this);

        // Register Core Ecosystem Singletons
        $this->singleton(\Papastro\Cache\CacheManager::class, fn($app) => new \Papastro\Cache\CacheManager($app));
        $this->singleton(\Papastro\Log\Logger::class, fn($app) => new \Papastro\Log\Logger($app));
        $this->singleton(\Papastro\Queue\QueueManager::class, fn($app) => new \Papastro\Queue\QueueManager($app));
        $this->singleton(\Papastro\Security\Encrypter::class, fn($app) => new \Papastro\Security\Encrypter());
        $this->singleton(\Papastro\Security\RateLimiter::class, fn($app) => new \Papastro\Security\RateLimiter($app->make(\Papastro\Cache\CacheManager::class)));
        $this->singleton(\Papastro\Debugger\QueryCollector::class, fn() => new \Papastro\Debugger\QueryCollector());
        $this->scoped(\Papastro\Debugger\Debugbar::class, fn($app) => new \Papastro\Debugger\Debugbar($app, $app->make(\Papastro\Debugger\QueryCollector::class)));
        $this->singleton(\Papastro\View\ViewEngine::class, fn($app) => new \Papastro\View\ViewEngine($app));
        $this->singleton(\Papastro\PWA\PwaManager::class, fn($app) => new \Papastro\PWA\PwaManager($app));
        // SeoManager holds per-page meta → must be shared inside a request but fresh for the next one.
        $this->scoped(\Papastro\SEO\SeoManager::class, fn($app) => new \Papastro\SEO\SeoManager($app));
        $this->singleton(\Papastro\Pasway\Pasway::class, fn($app) => new \Papastro\Pasway\Pasway($app));
    }

    /**
     * Register all of the base service providers.
     */
    protected function registerBaseServiceProviders(): void
    {
        // Core providers registered here
    }

    /**
     * Register the core class aliases in the container.
     */
    protected function registerCoreContainerAliases(): void
    {
        $coreAliases = [
            'app'       => [Application::class, Container::class],
            'config'    => [\Papastro\Config\Repository::class],
            'router'    => [\Papastro\Router\Router::class],
            'db'        => [\Papastro\ORM\Connection::class],
            'view'      => [\Papastro\View\ViewEngine::class],
            'pwa'       => [\Papastro\PWA\PwaManager::class],
            'seo'       => [\Papastro\SEO\SeoManager::class],
            'cache'     => [\Papastro\Cache\CacheManager::class],
            'log'       => [\Papastro\Log\Logger::class],
            'queue'     => [\Papastro\Queue\QueueManager::class],
            'encrypter' => [\Papastro\Security\Encrypter::class],
            'limiter'   => [\Papastro\Security\RateLimiter::class],
        ];

        foreach ($coreAliases as $alias => $abstracts) {
            foreach ($abstracts as $abstract) {
                $this->alias($abstract, $alias);
                break; // Map string alias to primary abstract class
            }
        }
    }

    /**
     * Register a service provider with the application.
     */
    public function register(mixed $provider, bool $force = false): mixed
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        $class = get_class($provider);
        $this->serviceProviders[$class] = $provider;
        $this->loadedProviders[$class] = true;

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     */
    public function getProvider(mixed $provider): mixed
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return $this->serviceProviders[$name] ?? null;
    }

    /**
     * Boot the application's service providers.
     */
    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->booted = true;
    }

    /**
     * Boot the given service provider.
     */
    protected function bootProvider(mixed $provider): void
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    /**
     * Bootstrap the application with given bootstrappers.
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }

    /**
     * Determine if the application has been bootstrapped before.
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Determine if the application has booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Determine if we are running in the console.
     */
    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * Determine if the application is in debug mode.
     */
    public function debug(): bool
    {
        if ($this->resolved('config')) {
            return (bool) $this->make('config')->get('app.debug', false);
        }

        return filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the application environment.
     */
    public function environment(): string
    {
        if ($this->resolved('config')) {
            return (string) $this->make('config')->get('app.env', 'production');
        }

        return (string) (getenv('APP_ENV') ?: 'production');
    }

    /**
     * Determine if the application is in a local environment.
     */
    public function isLocal(): bool
    {
        return $this->environment() === 'local';
    }

    /**
     * Determine if the application is in a production environment.
     */
    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    /**
     * Determine if the application is currently down for maintenance.
     */
    public function isDownForMaintenance(): bool
    {
        return is_file($this->storagePath('cache/down.json'));
    }

    /**
     * Get the maintenance mode payload data.
     */
    public function maintenanceModeData(): array
    {
        $path = $this->storagePath('cache/down.json');
        if (!is_file($path)) {
            return [];
        }
        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    /**
     * Instances that belong to a single request and must never leak into the next one.
     */
    protected array $requestInstances = [
        'request',
        \Papastro\Http\Request::class,
        'session',
        \Papastro\Session\Session::class,
        \Papastro\Router\Route::class,
    ];

    /**
     * Reset all per-request state. Called by the runtime after every request so a
     * long-lived worker behaves exactly like a fresh PHP-FPM request.
     */
    public function resetRequestState(): void
    {
        foreach ($this->requestInstances as $abstract) {
            $this->forgetInstance($abstract);
        }
        $this->forgetScopedInstances();

        if ($this->bound(\Papastro\Debugger\QueryCollector::class)) {
            $this->make(\Papastro\Debugger\QueryCollector::class)->reset();
        }
    }

    /**
     * Abort the application with an HTTP exception.
     */
    public function abort(int $code, string $message = '', array $headers = []): never
    {
        throw new \Papastro\Http\Exception\HttpException($code, $message, null, $headers);
    }
}
