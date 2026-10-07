const CACHE_NAME = 'gpt33-v1';
const ASSETS = [
  '/',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/assets/img/logo.png',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request).catch(() => {
        // Fallback or just let it fail silently for non-essential requests
      });
    })
  );
});
