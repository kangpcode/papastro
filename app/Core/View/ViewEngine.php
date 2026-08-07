<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.1.0
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
 * ViewEngine — Renders .pstro template files.
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

    public function __construct(Application $app)
    {
        $this->app      = $app;
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
        // For .pstro files, use the compiler
        if (str_ends_with($path, '.pstro')) {
            $compiled = $this->compiler->compile($path);
            return $this->evaluateCompiledTemplate($compiled, $data);
        }

        // For plain .php files, evaluate directly
        return $this->evaluatePHP($path, $data);
    }

    /**
     * Evaluate a compiled PHP template string.
     */
    protected function evaluateCompiledTemplate(string $code, array $data): string
    {
        // Write to a temp file in storage/cache
        $cacheDir  = $this->app->storagePath('cache/views');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $hash      = md5($code);
        $cachePath = $cacheDir . DIRECTORY_SEPARATOR . $hash . '.php';

        if (!file_exists($cachePath)) {
            file_put_contents($cachePath, $code);
        }

        return $this->evaluatePHP($cachePath, $data);
    }

    /**
     * Evaluate a PHP file and capture output.
     */
    protected function evaluatePHP(string $path, array $data): string
    {
        // $__data must be set BEFORE extract() so the compiled layout closure can capture it
        $__data    = $data;
        $__sections = $data['__sections'] ?? [];

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            include $path;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * Resolve a template name to a file path.
     *
     * Supports:
     *   - Module::template  →  app/Modules/{Module}/Pages/{template}.pstro
     *   - layouts.name      →  resources/layouts/{name}.pstro
     *   - name              →  resources/views/{name}.pstro
     */
    public function resolvePath(string $template): string
    {
        // Module::template syntax
        if (str_contains($template, '::')) {
            [$module, $view] = explode('::', $template, 2);
            $view = str_replace('.', DIRECTORY_SEPARATOR, $view);

            return $this->app->appPath("Modules/{$module}/Pages/{$view}.pstro");
        }

        // layouts.name → resources/layouts/{name}.pstro
        if (str_starts_with($template, 'layouts.')) {
            $name = substr($template, strlen('layouts.'));
            $name = str_replace('.', DIRECTORY_SEPARATOR, $name);
            return $this->app->resourcePath("layouts/{$name}.pstro");
        }

        // Convert dot notation to path
        $template = str_replace('.', DIRECTORY_SEPARATOR, $template);

        // Default: resources/views
        return $this->app->resourcePath("views/{$template}.pstro");
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
