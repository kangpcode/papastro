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

/**
 * ModuleManager — provides programmatic enable/disable/inspect of modules.
 */
class ModuleManager
{
    protected ModuleLoader $loader;
    protected string $modulesPath;

    public function __construct(ModuleLoader $loader, string $modulesPath)
    {
        $this->loader      = $loader;
        $this->modulesPath = $modulesPath;
    }

    /**
     * Enable a module by updating its module.json.
     */
    public function enable(string $name): bool
    {
        return $this->updateManifest($name, ['enabled' => true]);
    }

    /**
     * Disable a module by updating its module.json.
     */
    public function disable(string $name): bool
    {
        return $this->updateManifest($name, ['enabled' => false]);
    }

    /**
     * Update the module manifest.
     */
    protected function updateManifest(string $name, array $merge): bool
    {
        $manifest = $this->modulesPath . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'module.json';

        if (!file_exists($manifest)) {
            return false;
        }

        $data = json_decode(file_get_contents($manifest), true);
        $data = array_merge($data, $merge);

        file_put_contents($manifest, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return true;
    }

    /**
     * List all available modules (loaded or not).
     */
    public function all(): array
    {
        $modules = [];

        if (!is_dir($this->modulesPath)) {
            return $modules;
        }

        foreach (glob($this->modulesPath . '/*', GLOB_ONLYDIR) as $dir) {
            $name     = basename($dir);
            $manifest = $dir . DIRECTORY_SEPARATOR . 'module.json';
            $meta     = file_exists($manifest)
                ? json_decode(file_get_contents($manifest), true)
                : [];

            $modules[] = [
                'name'    => $name,
                'enabled' => $meta['enabled'] ?? true,
                'version' => $meta['version'] ?? '1.0.0',
                'loaded'  => $this->loader->isLoaded($name),
            ];
        }

        return $modules;
    }

    /**
     * Check if a module exists.
     */
    public function exists(string $name): bool
    {
        return is_dir($this->modulesPath . DIRECTORY_SEPARATOR . $name);
    }
}
