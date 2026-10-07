// Minimal Service Worker to satisfy PWA installation requirements on Android Chrome (Zebra TC22)
// Data & API requests are NOT cached to guarantee 100% real-time monitoring and reporting

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Network-only passthrough: do not cache any requests
    event.respondWith(fetch(event.request));
});
