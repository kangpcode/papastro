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

namespace Papastro\PWA;

use Papastro\Foundation\Application;

/**
 * PwaManager — generates manifest.json and sw.js for PWA support.
 */
class PwaManager
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Generate manifest.json in the public directory.
     */
    public function generateManifest(): string
    {
        $manifest = [
            'name' => config('pwa.name', config('app.name')),
            'short_name' => config('pwa.short_name', 'App'),
            'description' => config('pwa.description', ''),
            'start_url' => config('pwa.start_url', '/'),
            'display' => config('pwa.display', 'standalone'),
            'background_color' => config('pwa.background_color', '#0f172a'),
            'theme_color' => config('pwa.theme_color', '#0f172a'),
            'icons' => config('pwa.icons', []),
            'categories' => ['productivity'],
            'prefer_related_applications' => false,
            'orientation' => 'any',
            'lang' => config('app.locale', 'en'),
        ];

        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $outPath = $this->app->publicPath('manifest.json');
        file_put_contents($outPath, $json);

        return $outPath;
    }

    /**
     * Generate sw.js (Service Worker) in the public directory.
     */
    public function generateServiceWorker(): string
    {
        $cacheName = json_encode(config('pwa.cache_name', 'papastro-v1'));
        $precache = json_encode(config('pwa.precache', ['/', '/offline']));
        $strategy = config('pwa.strategy', 'cache-first');
        $offlinePage = json_encode(config('pwa.offline_page', '/offline'));

        $fetchStrategy = $this->getFetchStrategy($strategy);

        $sw = <<<JS
/*
 * Papastro Framework — Service Worker
 * created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 *
 * Strategy: {$strategy}
 */

const CACHE_NAME    = {$cacheName};
const PRECACHE_URLS = {$precache};
const OFFLINE_PAGE  = {$offlinePage};

// ── Install: precache assets ──────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('[Papastro SW] Precaching:', PRECACHE_URLS);
            return cache.addAll(PRECACHE_URLS);
        }).then(() => self.skipWaiting())
    );
});

// ── Activate: clean old caches ────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch: {$strategy} strategy ─────────────────────────────────
self.addEventListener('fetch', event => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Skip non-HTTP requests
    if (!event.request.url.startsWith('http')) return;

    event.respondWith(
        {$fetchStrategy}
    );
});

// ── Push notifications ────────────────────────────────────────────
self.addEventListener('push', event => {
    if (!event.data) return;
    const data = event.data.json();
    self.registration.showNotification(data.title || 'Papastro', {
        body: data.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/badge-72.png',
    });
});
JS;

        $outPath = $this->app->publicPath('sw.js');
        file_put_contents($outPath, $sw);

        return $outPath;
    }

    /**
     * Get the fetch strategy implementation code.
     */
    protected function getFetchStrategy(string $strategy): string
    {
        return match ($strategy) {
            'network-first' => <<<'JS'
fetch(event.request)
            .then(response => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                return response;
            })
            .catch(() => caches.match(event.request).then(cached => {
                return cached || caches.match(OFFLINE_PAGE);
            }))
JS,
            'stale-while-revalidate' => <<<'JS'
caches.match(event.request).then(cached => {
            const fetchPromise = fetch(event.request).then(response => {
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, response.clone()));
                return response;
            });
            return cached || fetchPromise;
        })
JS,
            default => <<<'JS'
caches.match(event.request).then(cached => {
            if (cached) return cached;
            return fetch(event.request)
                .then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                })
                .catch(() => caches.match(OFFLINE_PAGE));
        })
JS,
        };
    }
}
