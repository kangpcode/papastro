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

namespace Papastro\Config;

/**
 * Configuration Repository — loads and manages config files.
 */
class Repository
{
    protected array $items = [];
    protected string $configPath;
    protected array $loaded = [];

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->loadAll();
    }

    /**
     * Load all PHP config files from the config directory.
     */
    protected function loadAll(): void
    {
        if (!is_dir($this->configPath)) {
            return;
        }

        foreach (glob($this->configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            if (!isset($this->loaded[$key])) {
                $this->items[$key]  = require $file;
                $this->loaded[$key] = true;
            }
        }
    }

    /**
     * Get a configuration value using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        $file  = $parts[0];

        if (!isset($this->items[$file])) {
            return $default;
        }

        if (!isset($parts[1])) {
            return $this->items[$file];
        }

        return $this->dotGet($this->items[$file], $parts[1], $default);
    }

    /**
     * Resolve a dot-notation key from an array.
     */
    protected function dotGet(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set a configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $items = &$this->items;

        foreach ($parts as $part) {
            if (!isset($items[$part]) || !is_array($items[$part])) {
                $items[$part] = [];
            }
            $items = &$items[$part];
        }

        $items = $value;
    }

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get all configuration items.
     */
    public function all(): array
    {
        return $this->items;
    }
}
