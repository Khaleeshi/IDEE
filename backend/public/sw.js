// Service worker d'IDEE : pages publiques et fichiers statiques disponibles
// hors connexion (cahier des charges, section 10).
const CACHE = 'idee-v1';
const OFFLINE_URL = '/hors-ligne';

// Pages publiques qui peuvent être gardées en cache, dont les parcours guidés,
// utiles sur le terrain. Les pages personnelles (demandes, profil, mes projets)
// ne le sont jamais.
const CACHEABLE_PAGES = [
    /^\/$/,
    /^\/projets\/[0-9A-Za-z]{26}$/,
    /^\/membres\/[0-9A-Za-z]{26}$/,
    /^\/parcours$/,
    /^\/parcours\/[0-9A-Za-z]{26}$/,
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png'])));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)))),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Fichiers construits par Vite : leur nom change à chaque version, on sert le cache d'abord.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(request, copy));
                return response;
            })),
        );
        return;
    }

    // Pages : le réseau d'abord, puis la copie en cache, puis la page hors connexion.
    if (request.mode === 'navigate') {
        const cacheable = CACHEABLE_PAGES.some((pattern) => pattern.test(url.pathname));

        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (cacheable && response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() => caches.match(request).then((cached) => cached || caches.match(OFFLINE_URL))),
        );
    }
});

// Notifications push (NO-01) : affichage, puis ouverture de la page concernée au toucher.
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};

    event.waitUntil(self.registration.showNotification(data.title || 'IDEE', {
        body: data.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        data: { url: data.url || '/notifications' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(self.clients.openWindow(event.notification.data.url));
});
