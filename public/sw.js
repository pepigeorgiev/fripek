const CACHE_NAME = 'fripek-app-v1';

self.addEventListener('install', event => {
    console.log('Service Worker installing.');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll([
                    '/',
                    '/css/app.css',
                    '/js/app.js',
                    '/images/icon-192x192.png',
                    '/images/icon-512x512.png',
                    '/dashboard',
                    '/daily-transactions/create',
                    '/summary'
                ]);
            })
    );
});

self.addEventListener('activate', event => {
    console.log('Service Worker activating.');
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
        ))
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                return response || fetch(event.request)
                    .then(response => {
                        if (response.status === 200) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME)
                                .then(cache => cache.put(event.request, responseClone));
                        }
                        return response;
                    });
            })
            .catch(() => {
                return new Response('Offline content not available');
            })
    );
});

self.addEventListener('navigate', event => {
    console.log('Navigation attempt:', event.request.url);
});