const CACHE_NAME = 'casinoduliban-pwa-v3';
const ASSETS_TO_CACHE = [
  '/manifest.json',
  '/logo.png',
  '/favicon.ico'
];

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET' || event.request.mode === 'navigate') return;

  const requestUrl = new URL(event.request.url);
  if (requestUrl.origin !== self.location.origin) return;
  
  event.respondWith((async () => {
    try {
      return await fetch(event.request);
    } catch (_) {
      const cached = await caches.match(event.request);
      return cached || new Response('', {
        status: 503,
        statusText: 'Service Unavailable'
      });
    }
  })());
});
