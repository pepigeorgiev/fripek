const CACHE_NAME = 'fripek-v2';
const DOMAIN = 'https://fripekapp.mk';

const urlsToCache = [
    '/',
    '/install-app',
    '/css/app.css',
    '/js/app.js',
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
    '/offline.html'  // Create this file for offline fallback
];

self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Caching Files...');
                return cache.addAll(urlsToCache);
            })
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    console.log('Service Worker: Activated');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        console.log('Service Worker: Clearing Old Cache');
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', event => {
    console.log('Service Worker: Fetching');
    event.respondWith(
        fetch(event.request)
            .then(res => {
                const resClone = res.clone();
                caches.open(CACHE_NAME)
                    .then(cache => {
                        cache.put(event.request, resClone);
                    });
                return res;
            })
            .catch(() => caches.match(event.request))
    );
}); 