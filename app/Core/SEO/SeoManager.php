<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v1.2.0
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

/**
 * SeoManager — generates SEO meta tags and structured data for pages.
 */
class SeoManager
{
    protected Application $app;
    protected array $meta = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->meta = [
            'title' => config('seo.title', 'Papastro App'),
            'description' => config('seo.description', ''),
            'keywords' => config('seo.keywords', ''),
            'author' => config('seo.author', ''),
            'robots' => config('seo.robots', 'index, follow'),
            'og_type' => config('seo.og.type', 'website'),
            'og_image' => config('seo.og.image', ''),
            'canonical' => '',
        ];
    }

    /**
     * Set the page title.
     */
    public function title(string $title): static
    {
        $this->meta['title'] = $title;
        return $this;
    }

    /**
     * Set the meta description.
     */
    public function description(string $description): static
    {
        $this->meta['description'] = $description;
        return $this;
    }

    /**
     * Set the canonical URL.
     */
    public function canonical(string $url): static
    {
        $this->meta['canonical'] = $url;
        return $this;
    }

    /**
     * Set multiple meta properties at once.
     */
    public function set(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    /**
     * Render all SEO meta tags as HTML.
     */
    public function render(): string
    {
        $html = '';
        $title = htmlspecialchars($this->meta['title'] ?? '');
        $desc = htmlspecialchars($this->meta['description'] ?? '');
        $kw = htmlspecialchars($this->meta['keywords'] ?? '');
        $au = htmlspecialchars($this->meta['author'] ?? '');
        $rob = htmlspecialchars($this->meta['robots'] ?? 'index, follow');
        $url = config('app.url') . request()->path();

        $html .= "\n    <title>{$title}</title>\n";
        $html .= "    <meta name=\"description\" content=\"{$desc}\">\n";
        if ($kw)
            $html .= "    <meta name=\"keywords\" content=\"{$kw}\">\n";
        if ($au)
            $html .= "    <meta name=\"author\" content=\"{$au}\">\n";
        $html .= "    <meta name=\"robots\" content=\"{$rob}\">\n";

        // Canonical
        if ($this->meta['canonical'] || config('seo.canonical')) {
            $canonical = $this->meta['canonical'] ?: $url;
            $html .= "    <link rel=\"canonical\" href=\"{$canonical}\">\n";
        }

        // Open Graph
        $html .= "    <meta property=\"og:title\" content=\"{$title}\">\n";
        $html .= "    <meta property=\"og:description\" content=\"{$desc}\">\n";
        $html .= "    <meta property=\"og:type\" content=\"{$this->meta['og_type']}\">\n";
        $html .= "    <meta property=\"og:url\" content=\"{$url}\">\n";
        if ($this->meta['og_image']) {
            $html .= "    <meta property=\"og:image\" content=\"{$this->meta['og_image']}\">\n";
        }

        // Twitter Card
        $twitterCard = config('seo.twitter.card', 'summary_large_image');
        $twitterSite = config('seo.twitter.site', '');
        $twitterCreator = config('seo.twitter.creator', '');
        $html .= "    <meta name=\"twitter:card\" content=\"{$twitterCard}\">\n";
        $html .= "    <meta name=\"twitter:title\" content=\"{$title}\">\n";
        $html .= "    <meta name=\"twitter:description\" content=\"{$desc}\">\n";
        if ($twitterSite)
            $html .= "    <meta name=\"twitter:site\" content=\"{$twitterSite}\">\n";
        if ($twitterCreator)
            $html .= "    <meta name=\"twitter:creator\" content=\"{$twitterCreator}\">\n";

        return $html;
    }

    /**
     * Generate a JSON-LD structured data block.
     */
    public static function schema(string $type, mixed $data): string
    {
        $schema = match ($type) {
            'Article' => [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => is_object($data) ? ($data->title ?? '') : ($data['title'] ?? ''),
                'description' => is_object($data) ? ($data->excerpt ?? '') : ($data['excerpt'] ?? ''),
                'datePublished' => is_object($data) ? ($data->published_at ?? $data->created_at ?? '') : ($data['published_at'] ?? ''),
                'dateModified' => is_object($data) ? ($data->updated_at ?? '') : ($data['updated_at'] ?? ''),
                'author' => ['@type' => 'Person', 'name' => config('seo.author', '')],
                'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
            ],
            'WebSite' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'BreadcrumbList' => $data, // Pass pre-built breadcrumb schema
            default => array_merge(['@context' => 'https://schema.org', '@type' => $type], (array) $data),
        };

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return "<script type=\"application/ld+json\">\n{$json}\n</script>";
    }

    /**
     * Generate the PWA manifest link and service worker registration script.
     */
    public static function pwaHead(): string
    {
        $themeColor = config('pwa.theme_color', '#0f172a');
        $appName = htmlspecialchars(config('app.name', 'Papastro App'));

        return <<<HTML
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{$appName}">
    <meta name="theme-color" content="{$themeColor}">
    <link rel="manifest" href="/manifest.json">
HTML;
    }

    /**
     * Generate the service worker registration script.
     */
    public static function swScript(): string
    {
        return <<<'JS'
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('[Papastro PWA] SW registered:', reg.scope))
                    .catch(err => console.error('[Papastro PWA] SW failed:', err));
            });
        }
    </script>
JS;
    }
}
