const CACHE_NAME = 'fripek-v3';
const OFFLINE_DATA = 'offline-transactions';

const urlsToCache = [
    '/manifest.json',
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
    '/daily-transactions/create',
    '/css/app.css',
    '/js/app.js'
];

// Install event
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Caching Files...');
                return Promise.all(
                    urlsToCache.map(url => {
                        return fetch(url)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`Failed to fetch ${url}`);
                                }
                                return cache.put(url, response);
                            })
                            .catch(error => {
                                console.error(`Failed to cache ${url}:`, error);
                            });
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: All files cached');
                return self.skipWaiting();
            })
    );
});

// Activate event
self.addEventListener('activate', event => {
    console.log('Service Worker: Activated');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
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
    return self.clients.claim();
});

// Fetch event
self.addEventListener('fetch', event => {
    // Handle POST requests for transactions
    if (event.request.method === 'POST' && event.request.url.includes('/daily-transactions')) {
        event.respondWith(
            fetch(event.request.clone())
                .catch(async error => {
                    console.log('Offline, storing transaction...');
                    const formData = await event.request.clone().formData();
                    const transaction = {
                        url: event.request.url,
                        method: event.request.method,
                        data: Object.fromEntries(formData),
                        timestamp: new Date().getTime()
                    };

                    const db = await openDB();
                    const tx = db.transaction(OFFLINE_DATA, 'readwrite');
                    await tx.objectStore(OFFLINE_DATA).add(transaction);

                    return new Response(JSON.stringify({
                        success: true,
                        message: 'Трансакцијата е зачувана офлајн.',
                        offline: true
                    }), {
                        headers: { 'Content-Type': 'application/json' }
                    });
                })
        );
        return;
    }

    // Handle GET requests
    if (event.request.method === 'GET') {
        event.respondWith(
            caches.match(event.request)
                .then(response => {
                    if (response) {
                        return response;
                    }
                    return fetch(event.request);
                })
                .catch(() => {
                    if (event.request.mode === 'navigate') {
                        return caches.match('/');
                    }
                })
        );
    }
});

// Background sync
self.addEventListener('sync', event => {
    if (event.tag === 'sync-transactions') {
        event.waitUntil(syncOfflineTransactions());
    }
});

// IndexedDB helper
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('FripekOfflineDB', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(OFFLINE_DATA)) {
                db.createObjectStore(OFFLINE_DATA, { keyPath: 'timestamp' });
            }
        };
    });
}

// Sync function
async function syncOfflineTransactions() {
    console.log('Syncing offline transactions...');
    const db = await openDB();
    const tx = db.transaction(OFFLINE_DATA, 'readwrite');
    const store = tx.objectStore(OFFLINE_DATA);
    const transactions = await store.getAll();

    for (const transaction of transactions) {
        try {
            const formData = new FormData();
            Object.entries(transaction.data).forEach(([key, value]) => {
                formData.append(key, value);
            });

            const response = await fetch(transaction.url, {
                method: transaction.method,
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                await store.delete(transaction.timestamp);
                console.log('Successfully synced transaction:', transaction.timestamp);
            }
        } catch (error) {
            console.error('Failed to sync transaction:', error);
        }
    }
} 