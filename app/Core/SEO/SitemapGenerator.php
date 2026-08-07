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

namespace Papastro\SEO;

use Papastro\Foundation\Application;
use Papastro\Router\RouteCollection;

/**
 * SitemapGenerator — generates sitemap.xml from registered routes.
 */
class SitemapGenerator
{
    protected Application $app;
    protected RouteCollection $routes;

    public function __construct(Application $app, RouteCollection $routes)
    {
        $this->app    = $app;
        $this->routes = $routes;
    }

    /**
     * Generate and write sitemap.xml to public directory.
     */
    public function generate(): string
    {
        $baseUrl    = config('app.url', 'http://localhost');
        $changefreq = config('seo.sitemap.changefreq', 'weekly');
        $priority   = config('seo.sitemap.priority', '0.8');
        $now        = date('Y-m-d');

        $urls = '';
        foreach ($this->routes->all() as $route) {
            // Skip routes with parameters (dynamic routes)
            if (str_contains($route->getUri(), '{')) {
                continue;
            }
            // Only include GET routes
            if (!in_array('GET', $route->getMethods())) {
                continue;
            }

            $uri  = '/' . ltrim($route->getUri(), '/');
            $loc  = rtrim($baseUrl, '/') . $uri;
            $prio = $uri === '/' ? '1.0' : $priority;

            $urls .= <<<XML
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$now}</lastmod>
        <changefreq>{$changefreq}</changefreq>
        <priority>{$prio}</priority>
    </url>
XML;
            $urls .= "\n";
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$urls}</urlset>
XML;

        $outputPath = $this->app->publicPath(config('seo.sitemap.output', 'sitemap.xml'));
        file_put_contents($outputPath, $xml);

        return $outputPath;
    }
}
