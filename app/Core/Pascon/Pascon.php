<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0
 *  Pascon — Standalone Native SVG Icon System
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Papastro\Pascon;

/**
 * Pascon provides a high-performance, standalone SVG icon engine
 * for Papastro applications, eliminating emoji dependencies with
 * clean, scalable vector icons.
 */
class Pascon
{
    /**
     * In-memory icon cache / registry.
     *
     * @var array<string, string>
     */
    protected static array $icons = [];

    /**
     * Additional icon search paths.
     *
     * @var array<string>
     */
    protected static array $paths = [];

    /**
     * Render an icon as an SVG string.
     *
     * @param string $name
     * @param array $attributes
     * @return string
     */
    public static function svg(string $name, array $attributes = []): string
    {
        $name = strtolower(trim($name));
        $svgBody = static::getIconBody($name);

        if ($svgBody === null) {
            // Fallback placeholder icon (circle outline)
            $svgBody = '<circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />';
        }

        $size = $attributes['size'] ?? 24;
        $width = $attributes['width'] ?? $size;
        $height = $attributes['height'] ?? $size;
        $class = $attributes['class'] ?? 'pascon-icon pascon-' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $strokeWidth = $attributes['stroke-width'] ?? '2';
        $stroke = $attributes['stroke'] ?? 'currentColor';
        $fill = $attributes['fill'] ?? 'none';

        // Extract extra attributes
        $extraAttrs = '';
        foreach ($attributes as $k => $v) {
            if (in_array($k, ['size', 'width', 'height', 'class', 'stroke-width', 'stroke', 'fill'], true)) {
                continue;
            }
            $safeKey = htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
            $safeVal = htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
            $extraAttrs .= " {$safeKey}=\"{$safeVal}\"";
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 24 24" fill="%s" stroke="%s" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" class="%s"%s>%s</svg>',
            htmlspecialchars((string) $width, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $height, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($fill, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($stroke, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $strokeWidth, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
            $extraAttrs,
            $svgBody
        );
    }

    /**
     * Render from HTML tag syntax: <pascon:name attr="val" />
     *
     * @param string $name
     * @param string $rawAttributes
     * @return string
     */
    public static function renderTag(string $name, string $rawAttributes): string
    {
        $attributes = [];
        if (preg_match_all('/([\w\-:]+)=["\']([^"\']*)["\']/s', $rawAttributes, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }
        return static::svg($name, $attributes);
    }

    /**
     * Register a custom icon programmatically.
     *
     * @param string $name
     * @param string $svgBody
     */
    public static function register(string $name, string $svgBody): void
    {
        static::$icons[strtolower(trim($name))] = $svgBody;
    }

    /**
     * Add a directory to scan for .svg files.
     *
     * @param string $path
     */
    public static function addPath(string $path): void
    {
        if (is_dir($path) && !in_array($path, static::$paths, true)) {
            static::$paths[] = $path;
        }
    }

    /**
     * Retrieve the SVG inner body for the given icon.
     *
     * @param string $name
     * @return string|null
     */
    protected static function getIconBody(string $name): ?string
    {
        if (isset(static::$icons[$name])) {
            return static::$icons[$name];
        }

        // Check custom icon files in registered paths (e.g. resources/icons/)
        foreach (static::$paths as $path) {
            $file = rtrim($path, '/') . '/' . $name . '.svg';
            if (file_exists($file) && is_file($file)) {
                $content = (string) file_get_contents($file);
                // Strip outer <svg ...> and </svg> if present
                if (preg_match('/<svg[^>]*>(.*?)<\/svg>/is', $content, $m)) {
                    $content = $m[1];
                }
                static::$icons[$name] = trim($content);
                return static::$icons[$name];
            }
        }

        // Built-in Pascon icon library
        $library = static::builtInIcons();
        if (isset($library[$name])) {
            static::$icons[$name] = $library[$name];
            return static::$icons[$name];
        }

        return null;
    }

    /**
     * Built-in vector icon catalog (Lucide / Feather SVG paths).
     *
     * @return array<string, string>
     */
    protected static function builtInIcons(): array
    {
        return [
            // Rocket / Launch (Replaces 🚀)
            'rocket' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 9v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',

            // Island / Tropical (Replaces 🏝️)
            'island' => '<path d="M2 20c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.5 0 2.5 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M11 20v-7a4 4 0 0 1 4-4h1"/><path d="M13 14c2-2 3-5 3-7-2 0-5 1-7 3"/><path d="M16 11c2.5-.5 5-2 6-4-1.5 0-4 .5-6 2"/><path d="M15 8c0-3-1-5-3-7 0 2-1 4-2 6"/>',

            // Package / Module (Replaces 📦)
            'package' => '<path d="m16.5 9.4-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
            'box' => '<path d="m16.5 9.4-9-5.19M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',

            // Search / SEO (Replaces 🔍)
            'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
            'seo' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="M11 8v6M8 11h6"/>',

            // Mobile / Smartphone / PWA (Replaces 📱)
            'mobile' => '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
            'smartphone' => '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>',
            'pwa' => '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/><path d="M9 7l3 4-3 4M15 7l-3 4 3 4"/>',

            // Wrench / Tool / CLI (Replaces 🛠️)
            'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
            'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',

            // Bug / Debugging (Replaces 🐛)
            'bug' => '<rect width="8" height="14" x="8" y="6" rx="4"/><path d="m19 7-3 2M5 7l3 2M19 19l-3-2M5 19l3-2M20 13h-4M4 13h4M10 4l1 2M14 4l-1 2"/>',
            'debug' => '<rect width="8" height="14" x="8" y="6" rx="4"/><path d="m19 7-3 2M5 7l3 2M19 19l-3-2M5 19l3-2M20 13h-4M4 13h4M10 4l1 2M14 4l-1 2"/>',

            // Satellite / Antenna / Realtime (Replaces 📡)
            'antenna' => '<path d="M2 12 7 2M9 12l5-10M12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12 16v6M4.93 19.07A10 10 0 0 1 2 12M19.07 19.07A10 10 0 0 0 22 12M7.76 16.24A6 6 0 0 1 6 12M16.24 16.24A6 6 0 0 0 18 12"/>',
            'satellite' => '<path d="M2 12 7 2M9 12l5-10M12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12 16v6M4.93 19.07A10 10 0 0 1 2 12M19.07 19.07A10 10 0 0 0 22 12M7.76 16.24A6 6 0 0 1 6 12M16.24 16.24A6 6 0 0 0 18 12"/>',

            // Document / Memo / Blog (Replaces 📝)
            'document-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>',
            'pencil' => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>',

            // Star / Sparkles (Replaces 🌟)
            'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'sparkles' => '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/><path d="M5 3v4M3 5h4M19 17v4M17 19h4"/>',

            // Bolt / Lightning / Concurrency (Replaces ⚡)
            'bolt' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
            'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',

            // Gear / Settings / Maintenance (Replaces ⚙️)
            'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',

            // Arrows & Chevrons
            'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
            'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
            'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
            'chevron-up' => '<polyline points="18 15 12 9 6 15"/>',
            'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
            'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',

            // Status & Feedback
            'check' => '<polyline points="20 6 9 17 4 12"/>',
            'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
            'x-circle' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            'alert-circle' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',

            // Refresh / Sync (Replaces 🔄)
            'refresh' => '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/>',
            'sync' => '<path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/>',

            // Workers / Users (Replaces 👷)
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',

            // Eye / Watch (Replaces 👀)
            'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',

            // Link / Network (Replaces 🔗)
            'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',

            // Security & Database
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',

            // Code & Terminal
            'code' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
            'terminal' => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
            'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/>',
            'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        ];
    }
}
