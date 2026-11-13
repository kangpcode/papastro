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

namespace Papastro\Plugin;

/**
 * PluginManager — provides programmatic enable/disable/inspect of plugins.
 */
class PluginManager
{
    protected PluginLoader $loader;
    protected string $pluginsPath;

    public function __construct(PluginLoader $loader, string $pluginsPath)
    {
        $this->loader = $loader;
        $this->pluginsPath = $pluginsPath;
    }

    public function enable(string $name): bool
    {
        return $this->updateManifest($name, ['enabled' => true]);
    }

    public function disable(string $name): bool
    {
        return $this->updateManifest($name, ['enabled' => false]);
    }

    protected function updateManifest(string $name, array $merge): bool
    {
        $manifest = $this->pluginsPath . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'plugin.json';

        if (!file_exists($manifest)) {
            return false;
        }

        $data = json_decode(file_get_contents($manifest), true);
        $data = array_merge($data, $merge);

        file_put_contents($manifest, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return true;
    }

    public function all(): array
    {
        $plugins = [];

        if (!is_dir($this->pluginsPath)) {
            return $plugins;
        }

        foreach (glob($this->pluginsPath . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            $manifest = $dir . DIRECTORY_SEPARATOR . 'plugin.json';
            $meta = file_exists($manifest)
                ? json_decode(file_get_contents($manifest), true)
                : [];

            $plugins[] = [
                'name' => $name,
                'enabled' => $meta['enabled'] ?? true,
                'version' => $meta['version'] ?? '1.0.0',
                'loaded' => $this->loader->isLoaded($name),
            ];
        }

        return $plugins;
    }

    public function exists(string $name): bool
    {
        return is_dir($this->pluginsPath . DIRECTORY_SEPARATOR . $name);
    }
}
