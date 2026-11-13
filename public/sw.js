/*
 * Papastro Framework — Service Worker
 * created by kangpcode (Dhafa Nazula Permadi)
 * Free to use & extend. This watermark must remain in source code.
 *
 * Strategy: cache-first
 */

const CACHE_NAME    = "Papastro App-v1";
const PRECACHE_URLS = ["\/","\/offline"];
const OFFLINE_PAGE  = "\/offline";

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

// ── Fetch: cache-first strategy ─────────────────────────────────
self.addEventListener('fetch', event => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Skip non-HTTP requests
    if (!event.request.url.startsWith('http')) return;

    event.respondWith(
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