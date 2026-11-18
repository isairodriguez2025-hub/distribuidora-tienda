// Didi2 B2B — serviceWorker para PWA
// Cache-first para assets, network-first para HTML/API
const CACHE_NAME = 'did2-v13';
const STATIC_ASSETS = [
  '/',
  '/manifest.json',
  '/icon-192.svg',
  '/icon-512.svg',
  '/assets/css/app.css',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((names) =>
      Promise.all(
        names.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Solo cachear peticiones same-origin
  if (url.origin !== self.location.origin) return;

  // HTML y API: network-first
  if (event.request.headers.get('Accept')?.includes('text/html') ||
      url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }

  // Assets estáticos: cache-first
  event.respondWith(
    caches.match(event.request).then((cached) =>
      cached || fetch(event.request).then((response) => {
        if (response && response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        }
        return response;
      })
    )
  );
});
