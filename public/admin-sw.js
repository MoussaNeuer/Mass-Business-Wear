/* MBW Admin — Service Worker back-office (PWA séparée, scope /admin/) */
/* HTTP(S) : réseau d'abord pour toutes les données (jamais de cache stale sur commandes).
   Seuls les assets CSS/JS/img sont mis en cache (coquille UI offline). */

const CACHE = 'mbw-admin-v1';
const STATIC = [
  '../assets/css/tokens.css',
  '../assets/css/app.css',
  '../assets/css/admin.css',
  '../assets/js/admin.js',
  '../assets/vendor/bootstrap/css/bootstrap.min.css',
  '../assets/img/icon-192.png',
  '../assets/img/icon-512.png',
  '../assets/img/logo.jpeg'
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
  const isAsset = url.pathname.includes('/assets/');
  const isAdminPage = url.pathname.includes('/admin');

  // Assets statiques : cache d'abord (coquille UI), sinon réseau
  if (isAsset) {
    e.respondWith(
      caches.match(req).then((hit) =>
        hit || fetch(req).then((res) => {
          if (res && res.ok) {
            const copy = res.clone();
            caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => null);
          }
          return res;
        })
      )
    );
    return;
  }

  // HTML admin + API : TOUJOURS réseau (données fraîches), fallback page offline
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req).catch(() =>
        new Response(
          '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width">' +
          '<title>Admin hors ligne</title>' +
          '<body style="font-family:system-ui;background:#0A0A0A;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0">' +
          '<div style="text-align:center;padding:1.5rem">' +
          '<h1>Back-office hors ligne</h1>' +
          '<p style="color:#9ca3af">Impossible de joindre le serveur. Les données ne sont jamais mises en cache hors ligne.</p>' +
          '<button onclick="location.reload()" style="margin-top:.8rem;padding:.7rem 1.2rem;border:0;border-radius:12px;background:#D40000;color:#fff;font-weight:700">Réessayer</button>' +
          '</div></body>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        )
      )
    );
    return;
  }

  // API / mutations : réseau pur
  e.respondWith(fetch(req));
});
