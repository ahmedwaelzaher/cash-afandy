const options = new URL(self.location.href).searchParams;
const firebaseConfig = options.get('firebase');
const sdkVersion = options.get('sdk');

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

if (firebaseConfig && /^\d+\.\d+\.\d+$/.test(sdkVersion || '')) {
    importScripts(
        `https://www.gstatic.com/firebasejs/${sdkVersion}/firebase-app-compat.js`,
        `https://www.gstatic.com/firebasejs/${sdkVersion}/firebase-messaging-compat.js`,
    );
    firebase.initializeApp(JSON.parse(firebaseConfig));
    firebase.messaging();
}

self.addEventListener('fetch', function (event) {
    if (options.get('cache') !== '1' || event.request.method !== 'GET') return;
    const cachables = ['/vendor', '/assets'];
    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin || !cachables.some((cachable) => url.pathname.startsWith(cachable + '/'))) {
        return;
    }

    event.respondWith(
        caches.open('v1').then(async (cache) => {
            let response = await cache.match(event.request);

            // If the response is in the cache, return it
            if (response) return response;

            // Otherwise, fetch the response from the network and cache it
            response = await fetch(event.request);
            if (response.ok) cache.put(event.request, response.clone());

            return response;
        }),
    );
});
