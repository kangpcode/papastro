<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.2.0
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
    public const VERSION = '1.2.0';

    /**
     * The base path for the Papastro installation.
     */
    protected string $basePath;

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
        if ($basePath) {
            $this->setBasePath($basePath);
        }

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
        $this->aliases = [
            'app'    => [Application::class, Container::class],
            'config' => [\Papastro\Config\Repository::class],
            'router' => [\Papastro\Router\Router::class],
            'db'     => [\Papastro\ORM\Connection::class],
            'view'   => [\Papastro\View\ViewEngine::class],
            'pwa'    => [\Papastro\PWA\PwaManager::class],
            'seo'    => [\Papastro\SEO\SeoManager::class],
        ];
    }

    /**
     * Register a service provider with the application.
     */
    public function register(mixed $provider, bool $force = false): mixed
    {
        if ($registered = $this->getProvider($provider) && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        $this->serviceProviders[] = $provider;
        $this->loadedProviders[get_class($provider)] = true;

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
        return (bool) $this->make('config')->get('app.debug', false);
    }

    /**
     * Get the application environment.
     */
    public function environment(): string
    {
        return $this->make('config')->get('app.env', 'production');
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
     * Abort the application with an HTTP exception.
     */
    public function abort(int $code, string $message = '', array $headers = []): never
    {
        throw new \Papastro\Http\Exception\HttpException($code, $message, null, $headers);
    }
}
