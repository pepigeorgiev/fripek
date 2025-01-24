const CACHE_NAME = 'fripek-v1';

self.addEventListener('install', event => {
    console.log('Service Worker installing.');
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('Service Worker activating.');
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.map(key => caches.delete(key))
        ))
    );
});

self.addEventListener('fetch', event => {
    // Don't interfere with the root URL
    if (event.request.url === self.location.origin + '/') {
        return;
    }
    
    event.respondWith(
        fetch(event.request)
            .catch(() => {
                return caches.match(event.request);
            })
    );
}); 