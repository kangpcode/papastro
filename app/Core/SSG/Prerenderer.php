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

namespace Papastro\SSG;

use Papastro\Foundation\Application;
use Papastro\Http\Request;
use Papastro\Http\Kernel;

/**
 * Fast SSG (Static Site Generation) & Prerendering Engine.
 * Pre-computes HTML for static and island routes so web servers serve them instantly with zero PHP runtime cost.
 */
class Prerenderer
{
    public function __construct(protected Application $app) {}

    /**
     * Statically prerender all marked routes or static GET routes.
     *
     * @return array<string, string> Array mapping URI to generated file path.
     */
    public function prerender(array $additionalUris = []): array
    {
        $router = $this->app->make('router');
        $kernel = $this->app->make(Kernel::class);
        $publicPath = $this->app->publicPath();

        $uris = array_unique(array_merge($this->discoverPrerenderableUris(), $additionalUris));
        $generated = [];

        foreach ($uris as $uri) {
            $uri = '/' . ltrim($uri, '/');

            // Skip parameterized routes like /blog/{slug} if not explicitly passed
            if (preg_match('/\{[^}]+\}/', $uri)) {
                continue;
            }

            // Create fake GET request
            $request = Request::create($uri, 'GET');
            $response = $kernel->handle($request);

            if ($response->getStatusCode() === 200) {
                $targetFile = $uri === '/'
                    ? $publicPath . DIRECTORY_SEPARATOR . 'index.html'
                    : $publicPath . str_replace('/', DIRECTORY_SEPARATOR, $uri) . DIRECTORY_SEPARATOR . 'index.html';

                $dir = dirname($targetFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                file_put_contents($targetFile, $response->getContent());
                $generated[$uri] = $targetFile;
            }
        }

        return $generated;
    }

    /**
     * Discover routes eligible for prerendering.
     */
    protected function discoverPrerenderableUris(): array
    {
        $router = $this->app->make('router');
        $uris = [];

        foreach ($router->getRoutes()->all() as $route) {
            if (!in_array('GET', $route->getMethods(), true)) {
                continue;
            }

            // Explicitly marked route or static root
            if ($route->isPrerender()) {
                $uris[] = $route->getUri();
            }
        }

        return $uris;
    }
}
