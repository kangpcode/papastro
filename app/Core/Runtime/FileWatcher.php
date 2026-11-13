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

namespace Papastro\Runtime;

/**
 * FileWatcher — Monitors codebase directories for hot reloading during development.
 */
class FileWatcher
{
    protected array $paths;
    protected int $lastModified = 0;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
        $this->lastModified = $this->getLatestMtime();
    }

    /**
     * Check if any watched file has changed since last check.
     */
    public function hasChanged(): bool
    {
        clearstatcache();
        $latest = $this->getLatestMtime();

        if ($latest > $this->lastModified) {
            $this->lastModified = $latest;
            return true;
        }

        return false;
    }

    /**
     * Scan directories and return the highest modification timestamp.
     */
    protected function getLatestMtime(): int
    {
        $max = 0;
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                $max = max($max, (int) @filemtime($path));
            } elseif (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $max = max($max, (int) $file->getMTime());
                    }
                }
            }
        }

        return $max;
    }
}
