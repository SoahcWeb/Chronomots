const VERSION = 'chronomots-pwa-v1';
const STATIC_CACHE = `${VERSION}-static`;
const PAGE_CACHE = `${VERSION}-pages`;
const ASSET_CACHE = `${VERSION}-assets`;
const OFFLINE_URL = '/offline.html';
const STATIC_PAGES = ['/', '/play', '/leaderboards'];
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
    '/icons/apple-touch-icon.png',
    '/icons/favicon-32.png',
    ...STATIC_PAGES,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)),
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();

        await Promise.all(
            keys
                .filter((key) => ! [STATIC_CACHE, PAGE_CACHE, ASSET_CACHE].includes(key))
                .map((key) => caches.delete(key)),
        );

        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigationRequest(request, url));
        return;
    }

    if (isAssetRequest(url)) {
        event.respondWith(cacheFirst(request, ASSET_CACHE));
        return;
    }

    if (isStaticPageRequest(url)) {
        event.respondWith(networkFirst(request, PAGE_CACHE));
    }
});

async function handleNavigationRequest(request, url) {
    try {
        const response = await fetch(request);

        if (response.ok && isStaticPageRequest(url)) {
            const cache = await caches.open(PAGE_CACHE);
            cache.put(request, response.clone());
        }

        return response;
    } catch {
        const cachedPage = await caches.match(request);

        if (cachedPage) {
            return cachedPage;
        }

        return caches.match(OFFLINE_URL);
    }
}

async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);
    const cache = await caches.open(cacheName);
    cache.put(request, response.clone());

    return response;
}

async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(cacheName);
        cache.put(request, response.clone());

        return response;
    } catch {
        const cached = await caches.match(request);

        if (cached) {
            return cached;
        }

        return caches.match(OFFLINE_URL);
    }
}

function isStaticPageRequest(url) {
    return STATIC_PAGES.includes(url.pathname);
}

function isAssetRequest(url) {
    return url.pathname.startsWith('/build/assets/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/manifest.json'
        || url.pathname === '/favicon.ico';
}
