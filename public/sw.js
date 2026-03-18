const CACHE_NAME = 'gmao-v1';
const urlsToCache = [
    '/',
    '/build/app.css',    // ton CSS compile
    '/build/app.js',     // ton JS compile
];

// Installation : met en cache les fichiers essentiels
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

// Fetch : sert depuis le cache si disponible, sinon reseau
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => response || fetch(event.request))
    );
});
