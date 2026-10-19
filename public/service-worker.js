/* MASS BUSINESS WEAR — Service Worker boutique (PWA publique) */
/* Cache : shell statique réseau-d'abord, fallback offline ; pages HTML réseau-d'abord avec fallback. */

const CACHE = 'mbw-shop-v1';
const STATIC = [
  'assets/css/tokens.css',
  'assets/css/app.css',
  'assets/js/ui.js',
  'assets/js/cart.js',
  'assets/js/products.js',
  'assets/vendor/bootstrap/css/bootstrap.min.css',
  'assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
  'assets/img/icon-192.png',
  'assets/img/icon-512.png',
  'assets/img/icon-512-maskable.png',
  'assets/img/apple-touch-icon.png',
  'assets/img/favicon-64.png',
  'assets/img/logo.jpeg'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE)
      .then((c) => Promise.allSettled(STATIC.map((u) => c.add(u).catch(() => null))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  // Jamais de cache pour API, admin, recherche, checkout
  if (url.pathname.includes('/api/') || url.pathname.includes('/admin') ||
      url.pathname.includes('/cart') || url.pathname.includes('/checkout')) {
    return;
  }

  // Navigation : réseau d'abord, fallback page offline
  if (req.mode === 'navigate') {
    const scope = self.registration ? self.registration.scope : '/';
    e.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => null);
          return res;
        })
        .catch(() =>
          caches.match(req).then((hit) => hit || caches.match(scope) ||
            new Response('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Hors ligne</title><body style="font-family:system-ui;background:#0A0A0A;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0"><div style="text-align:center;padding:1.5rem"><h1>Vous êtes hors ligne</h1><p style="color:#9ca3af">La boutique sera de retour dès que vous serez reconnecté.</p><a style="color:#f87171;font-weight:700" href="' + scope + '">Réessayer</a></div></body>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } })
          )
        )
    );
    return;
  }

  // Assets : stale-while-revalidate
  e.respondWith(
    caches.match(req).then((hit) => {
      const net = fetch(req)
        .then((res) => {
          if (res && res.ok) {
            const copy = res.clone();
            caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => null);
          }
          return res;
        })
        .catch(() => hit);
      return hit || net;
    })
  );
});
