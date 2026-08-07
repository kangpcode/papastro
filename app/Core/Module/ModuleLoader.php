<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Module;

use Papastro\Foundation\Application;

/**
 * ModuleLoader — discovers and loads all active modules.
 */
class ModuleLoader
{
    protected Application $app;
    protected array $modules = [];
    protected string $modulesPath;

    public function __construct(Application $app)
    {
        $this->app         = $app;
        $this->modulesPath = $app->appPath('Modules');
    }

    /**
     * Discover and load all active modules.
     */
    public function load(): void
    {
        $config   = $this->app->make('config');
        $disabled = $config->get('modules.disabled', []);

        if ($config->get('modules.auto_discover', true)) {
            $this->autoDiscover($disabled);
        } else {
            $this->loadExplicit($config->get('modules.enabled', []), $disabled);
        }
    }

    /**
     * Auto-discover all module directories with dependency-aware ordering.
     */
    protected function autoDiscover(array $disabled): void
    {
        if (!is_dir($this->modulesPath)) {
            return;
        }

        $dirs = glob($this->modulesPath . '/*', GLOB_ONLYDIR);
        if (!$dirs) return;

        // Build a pending list of [name => dir]
        $pending = [];
        foreach ($dirs as $dir) {
            $name = basename($dir);
            if (!in_array($name, $disabled)) {
                $pending[$name] = $dir;
            }
        }

        // Multi-pass loading to resolve dependency order
        $maxPasses = count($pending) + 1;
        $pass = 0;

        while (!empty($pending) && $pass < $maxPasses) {
            $pass++;
            $loaded = 0;

            foreach ($pending as $name => $dir) {
                $manifest = $dir . DIRECTORY_SEPARATOR . 'module.json';
                if (!file_exists($manifest)) {
                    unset($pending[$name]);
                    continue;
                }

                $meta = json_decode(file_get_contents($manifest), true);

                // Check if all required modules are already loaded
                $requires = $meta['requires'] ?? [];
                $allLoaded = true;
                foreach ($requires as $dep) {
                    if (!isset($this->modules[$dep])) {
                        $allLoaded = false;
                        break;
                    }
                }

                if ($allLoaded) {
                    $this->loadModule($name, $dir);
                    unset($pending[$name]);
                    $loaded++;
                }
            }

            if ($loaded === 0) break; // No progress — circular or missing dep
        }

        // Report unresolvable modules (missing dependencies)
        foreach ($pending as $name => $dir) {
            error_log("[Papastro] Module [{$name}] could not be loaded — unresolved dependencies.");
        }
    }


    /**
     * Load explicitly listed modules.
     */
    protected function loadExplicit(array $enabled, array $disabled): void
    {
        foreach ($enabled as $name) {
            if (in_array($name, $disabled)) continue;

            $dir = $this->modulesPath . DIRECTORY_SEPARATOR . $name;
            if (is_dir($dir)) {
                $this->loadModule($name, $dir);
            }
        }
    }

    /**
     * Load a single module.
     */
    protected function loadModule(string $name, string $path): void
    {
        $manifest = $path . DIRECTORY_SEPARATOR . 'module.json';

        if (!file_exists($manifest)) {
            return;
        }

        $meta = json_decode(file_get_contents($manifest), true);

        if (!($meta['enabled'] ?? true)) {
            return;
        }

        // Check module dependencies
        foreach ($meta['requires'] ?? [] as $dep) {
            if (!isset($this->modules[$dep])) {
                throw new \RuntimeException("Module [{$name}] requires [{$dep}] which is not loaded.");
            }
        }

        $this->modules[$name] = [
            'name'    => $name,
            'path'    => $path,
            'meta'    => $meta,
            'enabled' => true,
        ];

        // Load module routes
        $this->loadModuleRoutes($name, $path);

        // Load module service provider if exists
        $this->loadModuleProvider($name);
    }

    /**
     * Load routes from the module's Routes directory.
     */
    protected function loadModuleRoutes(string $name, string $path): void
    {
        $routesDir = $path . DIRECTORY_SEPARATOR . 'Routes';

        foreach (['web.php', 'api.php'] as $file) {
            $routeFile = $routesDir . DIRECTORY_SEPARATOR . $file;

            if (file_exists($routeFile)) {
                $router = $this->app->make(\Papastro\Router\Router::class);
                \Papastro\Router\RouteFacade::setRouter($router);

                if ($file === 'api.php') {
                    $router->group(['prefix' => 'api'], function () use ($routeFile) {
                        require $routeFile;
                    });
                } else {
                    require $routeFile;
                }
            }
        }
    }

    /**
     * Load the module's service provider if it exists.
     */
    protected function loadModuleProvider(string $name): void
    {
        $providerClass = "App\\Modules\\{$name}\\{$name}ServiceProvider";

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    /**
     * Get all loaded modules.
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Check if a module is loaded.
     */
    public function isLoaded(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Get the path for a module.
     */
    public function getModulePath(string $name): string
    {
        return $this->modulesPath . DIRECTORY_SEPARATOR . $name;
    }
}
