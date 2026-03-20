const CACHE_NAME = 'gmao-v2';

// Installation : on ne pré-cache rien pour éviter les erreurs de fichiers hashés
self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    // Supprimer les anciens caches
    event.waitUntil(
        caches.keys().then(names =>
            Promise.all(
                names.filter(name => name !== CACHE_NAME).map(name => caches.delete(name))
            )
        )
    );
});

// Stratégie network-first : réseau d'abord, cache en fallback
self.addEventListener('fetch', event => {
    // Ignorer les requêtes non-GET
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Mettre en cache la réponse pour usage offline
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                return response;
            })
            .catch(() => caches.match(event.request))
    );
});
