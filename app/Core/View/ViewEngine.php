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

namespace Papastro\View;

use Papastro\Foundation\Application;

/**
 * ViewEngine — Renders .pstro template files with high-speed mtime-based OPcache caching.
 *
 * Template resolution:
 *   - 'welcome'         → resources/views/welcome.pstro (or .php)
 *   - 'Blog::index'     → app/Modules/Blog/Pages/index.pstro
 *   - 'layouts.base'    → resources/layouts/base.pstro
 */
class ViewEngine
{
    protected Application $app;
    protected Compiler $compiler;
    protected array $globalData = [];
    protected array $resolvedPaths = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->compiler = new Compiler($app);
    }

    /**
     * Render a view template and return the HTML string.
     */
    public function render(string $template, array $data = []): string
    {
        $path = $this->resolvePath($template);

        if (!file_exists($path)) {
            throw new \RuntimeException("View [{$template}] not found at [{$path}].");
        }

        $data = array_merge($this->globalData, $data);

        return $this->renderFile($path, $data);
    }

    /**
     * Render a file path directly.
     */
    public function renderFile(string $path, array $data = []): string
    {
        if (str_ends_with($path, '.pstro')) {
            $cachePath = $this->getCompiledPath($path);

            if ($this->isExpired($path, $cachePath)) {
                $compiled = $this->compiler->compile($path);
                $cacheDir = dirname($cachePath);
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                file_put_contents($cachePath, $compiled, LOCK_EX);
            }

            return $this->evaluatePHP($cachePath, $data);
        }

        return $this->evaluatePHP($path, $data);
    }

    /**
     * Get the deterministic cache file path for a template path.
     */
    public function getCompiledPath(string $path): string
    {
        $hash = md5($path);
        return $this->app->storagePath("cache/views/{$hash}.php");
    }

    /**
     * Determine if a compiled view is missing or older than the source template.
     */
    public function isExpired(string $sourcePath, string $compiledPath): bool
    {
        if (!file_exists($compiledPath)) {
            return true;
        }

        return filemtime($sourcePath) > filemtime($compiledPath);
    }

    /**
     * Pre-compile a template file and return the compiled path.
     */
    public function compileTemplateFile(string $path): string
    {
        $cachePath = $this->getCompiledPath($path);
        $compiled = $this->compiler->compile($path);
        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cachePath, $compiled, LOCK_EX);
        return $cachePath;
    }

    /**
     * Evaluate a PHP file and capture output.
     */
    protected function evaluatePHP(string $path, array $data): string
    {
        $__data = $data;
        $__sections = $data['__sections'] ?? [];

        extract($data, EXTR_SKIP);

        $obLevel = ob_get_level();
        ob_start();

        try {
            include $path;
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            throw $e;
        }

        return (string) ob_get_clean();
    }

    /**
     * Resolve a template name to a file path (with in-memory path cache).
     */
    public function resolvePath(string $template): string
    {
        if (isset($this->resolvedPaths[$template])) {
            return $this->resolvedPaths[$template];
        }

        // Module::template syntax
        if (str_contains($template, '::')) {
            [$module, $view] = explode('::', $template, 2);
            $view = str_replace('.', DIRECTORY_SEPARATOR, $view);

            return $this->resolvedPaths[$template] = $this->app->appPath("Modules/{$module}/Pages/{$view}.pstro");
        }

        // layouts.name → resources/layouts/{name}.pstro
        if (str_starts_with($template, 'layouts.')) {
            $name = substr($template, strlen('layouts.'));
            $name = str_replace('.', DIRECTORY_SEPARATOR, $name);
            return $this->resolvedPaths[$template] = $this->app->resourcePath("layouts/{$name}.pstro");
        }

        // Convert dot notation to path
        $normalized = str_replace('.', DIRECTORY_SEPARATOR, $template);

        // Default: resources/views
        return $this->resolvedPaths[$template] = $this->app->resourcePath("views/{$normalized}.pstro");
    }

    /**
     * Check if a view template exists.
     */
    public function exists(string $template): bool
    {
        return file_exists($this->resolvePath($template));
    }

    /**
     * Add a global data variable available to all views.
     */
    public function share(string $key, mixed $value): void
    {
        $this->globalData[$key] = $value;
    }

    /**
     * Get the compiled content of a template for inspection.
     */
    public function getCompiledContent(string $template): string
    {
        $path = $this->resolvePath($template);
        return $this->compiler->compile($path);
    }
}
