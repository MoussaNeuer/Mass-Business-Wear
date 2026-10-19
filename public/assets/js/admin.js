/**
 * MBW Admin — flash toasts auto-dismiss, confirmations, helpers.
 * Aucune dépendance.
 */
(function () {
  'use strict';

  /* ---------- CSRF global ----------
   * Le token est dans <meta name="csrf-token">. Tout <form method=post>
   * sans champ _token le reçoit automatiquement au moment du submit
   * (phase capture, donc avant la confirmation et avant l'envoi).
   */
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  var CSRF = csrfMeta ? (csrfMeta.getAttribute('content') || '') : '';
  window.MBW_CSRF = CSRF;

  if (CSRF) {
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      var method = (form.getAttribute('method') || 'get').toLowerCase();
      if (method !== 'post') return;
      if (form.querySelector('input[name="_token"]')) return;
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = '_token';
      input.value = CSRF;
      form.appendChild(input);
    }, true);

    // Requêtes AJAX (fetch) : en-tête X-CSRF-Token accepté par Security::checkCsrf()
    var origFetch = window.fetch;
    if (origFetch) {
      window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || (input && input.method) || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
          init.headers = init.headers || {};
          if (init.headers instanceof Headers) {
            init.headers.set('X-CSRF-Token', CSRF);
          } else if (Array.isArray(init.headers)) {
            init.headers.push(['X-CSRF-Token', CSRF]);
          } else {
            init.headers['X-CSRF-Token'] = CSRF;
          }
        }
        return origFetch(input, init);
      };
    }
  }

  /* ---------- Flash toasts ---------- */
  var stack = document.getElementById('admin-flash');
  if (stack) {
    var flashes = stack.querySelectorAll('.admin-flash');
    flashes.forEach(function (el, i) {
      setTimeout(function () {
        el.classList.add('is-leaving');
        setTimeout(function () { el.remove(); }, 320);
      }, 3200 + i * 180);
    });
    // Vide la pile après animation
    setTimeout(function () {
      if (stack && !stack.querySelector('.admin-flash')) stack.remove();
    }, 3200 + flashes.length * 180 + 400);
  }

  /* ---------- Confirmations + verrou anti double-submit ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.confirm) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
        return;
      }
    }
    var btn = form.querySelector('[type="submit"]');
    if (btn && !btn.disabled) {
      btn.disabled = true;
      btn.dataset.originalText = btn.textContent;
      btn.textContent = btn.dataset.loading || '…';
      setTimeout(function () {
        if (document.contains(btn)) {
          btn.disabled = false;
          btn.textContent = btn.dataset.originalText;
        }
      }, 4000);
    }
  });

  /* ---------- Confirmation boutons data-confirm (click) ---------- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm-click]');
    if (el) {
      var msg = el.getAttribute('data-confirm-click');
      if (msg && !window.confirm(msg)) {
        e.preventDefault();
        e.stopPropagation();
      }
    }
  });

  /* ---------- Filtres admin : submit auto au changement ---------- */
  document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
    el.addEventListener('change', function () {
      var form = el.form || el.closest('form');
      if (form) form.submit();
    });
  });

  /* ---------- Recherche : submit au debounce via Enter ---------- */
  document.querySelectorAll('[data-admin-search]').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        var form = input.form || input.closest('form');
        if (form) form.submit();
      }
    });
  });

  /* ---------- Aperçu image upload ---------- */
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
      var target = document.querySelector(input.getAttribute('data-preview'));
      if (!target) return;
      var file = input.files && input.files[0];
      if (!file) { target.style.display = 'none'; return; }
      var url = URL.createObjectURL(file);
      target.src = url;
      target.style.display = 'block';
    });
  });

  /* ---------- Lightbox photos (commandes) ---------- */
  var lightbox = null;
  function ensureLightbox() {
    if (lightbox) return lightbox;
    lightbox = document.createElement('div');
    lightbox.className = 'admin-lightbox';
    lightbox.hidden = true;
    lightbox.innerHTML = '<img alt="Photo agrandie"><button type="button" class="admin-lightbox__close" aria-label="Fermer">✕</button>';
    document.body.appendChild(lightbox);
    var img = lightbox.querySelector('img');
    var close = function () { lightbox.hidden = true; };
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox || e.target === close) close();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && lightbox && !lightbox.hidden) close();
    });
    return lightbox;
  }
  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest('[data-lightbox]') : null;
    if (!el) return;
    e.preventDefault();
    var lb = ensureLightbox();
    lb.querySelector('img').src = el.getAttribute('data-lightbox') || '';
    lb.hidden = false;
  });

  /* ---------- Toast utilitaire (utilisé par admin-upload.js) ---------- */
  window.mbwToast = function (message, type) {
    type = type || 'ok';
    var el = document.createElement('div');
    el.className = 'admin-flash admin-flash--' + (type === 'err' ? 'err' : 'ok');
    el.textContent = message;
    el.setAttribute('role', 'status');
    var host = document.getElementById('admin-flash');
    if (!host) {
      host = document.createElement('div');
      host.id = 'admin-flash';
      host.className = 'admin-flash-stack';
      host.setAttribute('role', 'status');
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }
    host.appendChild(el);
    setTimeout(function () {
      el.classList.add('is-leaving');
      setTimeout(function () { el.remove(); }, 320);
    }, 3400);
  };

  /* ---------- Smooth scroll ancres (zones) ---------- */
  if (location.hash) {
    var target = document.querySelector(location.hash);
    if (target) {
      setTimeout(function () {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }
  }
})();
