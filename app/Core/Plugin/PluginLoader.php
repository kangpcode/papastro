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

namespace Papastro\Plugin;

use Papastro\Foundation\Application;

/**
 * PluginLoader — discovers and loads all active third-party (plug-and-play) plugins
 * from the project-level `plugins/` directory.
 */
class PluginLoader
{
    protected Application $app;
    protected array $plugins = [];
    protected string $pluginsPath;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->pluginsPath = $app->basePath('plugins');
    }

    /**
     * Discover and load all active plugins.
     */
    public function load(): void
    {
        $config = $this->app->make('config');

        if (!$config->get('app.plugins.enabled', true)) {
            return;
        }

        $disabled = (array) $config->get('app.plugins.disabled', []);

        if ($config->get('app.plugins.auto_discover', true)) {
            $this->autoDiscover($disabled);
        } else {
            $this->loadExplicit((array) $config->get('app.plugins.explicit', []), $disabled);
        }
    }

    /**
     * Auto-discover all plugin directories with dependency-aware ordering.
     */
    protected function autoDiscover(array $disabled): void
    {
        if (!is_dir($this->pluginsPath)) {
            return;
        }

        $dirs = glob($this->pluginsPath . '/*', GLOB_ONLYDIR);
        if (!$dirs) {
            return;
        }

        $pending = [];
        foreach ($dirs as $dir) {
            $name = basename($dir);
            if (!in_array($name, $disabled)) {
                $pending[$name] = $dir;
            }
        }

        $maxPasses = count($pending) + 1;
        $pass = 0;

        while (!empty($pending) && $pass < $maxPasses) {
            $pass++;
            $loaded = 0;

            foreach ($pending as $name => $dir) {
                $manifest = $dir . DIRECTORY_SEPARATOR . 'plugin.json';
                if (!file_exists($manifest)) {
                    unset($pending[$name]);
                    continue;
                }

                $meta = json_decode((string) file_get_contents($manifest), true) ?: [];

                $requires = $meta['requires'] ?? [];
                $allLoaded = true;
                foreach ($requires as $dep) {
                    if (!isset($this->plugins[$dep])) {
                        $allLoaded = false;
                        break;
                    }
                }

                if ($allLoaded) {
                    $this->loadPlugin($name, $dir);
                    unset($pending[$name]);
                    $loaded++;
                }
            }

            if ($loaded === 0) {
                break;
            }
        }

        foreach ($pending as $name => $dir) {
            error_log("[Papastro] Plugin [{$name}] could not be loaded — unresolved dependencies.");
        }
    }

    /**
     * Load explicitly listed plugins.
     */
    protected function loadExplicit(array $enabled, array $disabled): void
    {
        foreach ($enabled as $name) {
            if (in_array($name, $disabled)) {
                continue;
            }

            $dir = $this->pluginsPath . DIRECTORY_SEPARATOR . $name;
            if (is_dir($dir)) {
                $this->loadPlugin($name, $dir);
            }
        }
    }

    /**
     * Load a single plugin.
     */
    protected function loadPlugin(string $name, string $path): void
    {
        $manifest = $path . DIRECTORY_SEPARATOR . 'plugin.json';

        if (!file_exists($manifest)) {
            return;
        }

        $meta = json_decode((string) file_get_contents($manifest), true) ?: [];

        if (!($meta['enabled'] ?? true)) {
            return;
        }

        foreach ($meta['requires'] ?? [] as $dep) {
            if (!isset($this->plugins[$dep])) {
                throw new \RuntimeException("Plugin [{$name}] requires [{$dep}] which is not loaded.");
            }
        }

        // Load plugin's own autoloader if present (plug-and-play)
        $autoload = $path . DIRECTORY_SEPARATOR . 'autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        $this->plugins[$name] = [
            'name' => $name,
            'path' => $path,
            'meta' => $meta,
            'enabled' => true,
        ];

        $this->loadPluginRoutes($name, $path);
        $this->loadPluginProvider($name);
    }

    /**
     * Load routes from the plugin's Routes directory.
     */
    protected function loadPluginRoutes(string $name, string $path): void
    {
        $routesDir = $path . DIRECTORY_SEPARATOR . 'Routes';

        foreach (['web.php', 'api.php'] as $file) {
            $routeFile = $routesDir . DIRECTORY_SEPARATOR . $file;

            if (file_exists($routeFile)) {
                $router = $this->app->make(\Papastro\Router\Router::class);
                \Papastro\Router\RouteFacade::setRouter($router);

                if ($file === 'api.php') {
                    $router->group(['prefix' => 'api', 'middleware' => ['api']], function () use ($routeFile) {
                        require $routeFile;
                    });
                } else {
                    $router->group(['middleware' => ['web']], function () use ($routeFile) {
                        require $routeFile;
                    });
                }
            }
        }
    }

    /**
     * Load the plugin's service provider if it exists.
     */
    protected function loadPluginProvider(string $name): void
    {
        $providerClass = "Plugins\\{$name}\\{$name}ServiceProvider";

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    /**
     * Get all loaded plugins.
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Check if a plugin is loaded.
     */
    public function isLoaded(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Get the path for a plugin.
     */
    public function getPluginPath(string $name): string
    {
        return $this->pluginsPath . DIRECTORY_SEPARATOR . $name;
    }
}
