// Service worker : installation de l'application (PWA) et chargement plus rapide.
//  - assets Vite (/build/) : cache d'abord ;
//  - navigations : réseau d'abord, repli sur la page en cache (SPA) ;
//  - API et écritures : toujours le réseau (l'application nécessite une connexion).

const CACHE_NAME = 'edl-anef-v5';

const STATIC_ASSETS = [
    '/',
    '/manifest.json',
];

// Installation : mise en cache des ressources statiques
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// Activation : suppression des anciens caches (dont celui des données d'EDL de l'ancien mode hors-ligne)
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((names) =>
            Promise.all(names.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name)))
        )
    );
    self.clients.claim();
});

/** Réseau d'abord ; en cas d'échec, dernière réponse en cache, sinon erreur réseau propre. */
async function networkFirst(request, { navigation = false } = {}) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const clone = response.clone();
            const shell = navigation ? response.clone() : null;
            caches.open(CACHE_NAME).then((cache) => {
                cache.put(request, clone);
                // Page de l'application (SPA) : gardée aussi comme page de repli
                if (shell) cache.put('/', shell);
            });
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) return cached;
        if (navigation) {
            const home = await caches.match('/');
            if (home) return home;
        }
        return Response.error();
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-GET et les requêtes vers d'autres origines
    if (request.method !== 'GET' || url.origin !== location.origin) {
        return;
    }

    // API, photos et PDF : jamais mis en cache
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/edl/photos/') || /^\/edl\/\d+\/pdf/.test(url.pathname)) {
        return;
    }

    // Assets statiques (build Vite) : cache d'abord
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Navigation (SPA) : réseau d'abord, repli sur la page en cache
    event.respondWith(networkFirst(request, { navigation: request.mode === 'navigate' }));
});
