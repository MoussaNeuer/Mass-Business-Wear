/* MBW Admin — installation PWA + SW admin */
(function () {
  'use strict';

  // Base dynamique : MBW_BASE défini par le layout (aucun chemin en dur)
  var base = (window.MBW_BASE || (function () {
    // Repli : racine détectée depuis le lien manifest de la page
    var m = document.querySelector('link[rel="manifest"]');
    if (m && m.href) return m.href.replace(/manifest\.admin\.json.*$/, '');
    return '/';
  })()).replace(/\/?$/, '/');

  // ------------------------------------------------ Service worker admin
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(base + 'admin-sw.js', { scope: base + 'admin/' })
        .catch(function (e) { console.warn('SW admin', e); });
    });
  }

  // ------------------------------------------------ Installation
  var deferred = null;
  var BTN = 'admin-install-btn';

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferred = e;
    showButton('install');
  });

  window.addEventListener('appinstalled', function () {
    deferred = null;
    showButton('installed');
  });

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true;
  }

  function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent) ||
      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  }

  function showButton(mode) {
    var btn = document.getElementById(BTN);
    if (!btn) return;
    if (mode === 'installed' || isStandalone()) {
      btn.hidden = false;
      btn.dataset.mode = 'installed';
      btn.setAttribute('aria-label', 'Application installée');
      btn.title = 'Application déjà installée sur cet appareil';
      btn.querySelector('.admin-install__label').textContent = 'Installée';
      return;
    }
    btn.hidden = false;
    btn.dataset.mode = mode;
    if (mode === 'ios') {
      btn.querySelector('.admin-install__label').textContent = 'Installer';
      btn.title = 'Ajouter aux écrans d\u2019accueil';
    } else {
      btn.querySelector('.admin-install__label').textContent = 'Installer';
      btn.title = 'Télécharger le back-office sur cet appareil';
    }
  }

  function sheet(html) {
    var overlay = document.createElement('div');
    overlay.className = 'admin-pwa-sheet-overlay';
    overlay.innerHTML = '<div class="admin-pwa-sheet" role="dialog" aria-modal="true">' + html + '</div>';
    document.body.appendChild(overlay);
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.remove();
    });
    var close = overlay.querySelector('[data-close]');
    if (close) close.addEventListener('click', function () { overlay.remove(); });
    return overlay;
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#' + BTN);
    if (!btn) return;
    e.preventDefault();

    if (btn.dataset.mode === 'installed') {
      sheet(
        '<h3>Back-office déjà installé</h3>' +
        '<p>MBW Admin est déjà sur cet appareil. Ouvrez-le depuis votre écran d\u2019accueil.</p>' +
        '<button class="admin-btn admin-btn--primary admin-btn--block" data-close>Compris</button>'
      );
      return;
    }

    if (deferred) {
      deferred.prompt();
      deferred.userChoice.then(function (choice) {
        if (choice.outcome === 'accepted') showButton('installed');
        deferred = null;
      });
      return;
    }

    if (isIos()) {
      sheet(
        '<h3>Installer sur iPhone / iPad</h3>' +
        '<ol class="admin-pwa-steps">' +
        '<li>Ouvrez cette page dans <strong>Safari</strong></li>' +
        '<li>Touchez le bouton <strong>Partager</strong> ⬆︎</li>' +
        '<li>Choisissez <strong>Sur l\u2019écran d\u2019accueil</strong></li>' +
        '<li>Touchez <strong>Ajouter</strong></li>' +
        '</ol>' +
        '<button class="admin-btn admin-btn--primary admin-btn--block" data-close>Compris</button>'
      );
      return;
    }

    sheet(
      '<h3>Installer le back-office</h3>' +
      '<p>Ce navigateur n\u2019a pas proposé l\u2019installation automatique.</p>' +
      '<ol class="admin-pwa-steps">' +
      '<li>Ouvrez le <strong>menu du navigateur</strong> (⋮ ou ⋯)</li>' +
      '<li>Choisissez <strong>Installer l\u2019application</strong> / <strong>Ajouter à l\u2019écran d\u2019accueil</strong></li>' +
      '<li>Confirmez <strong>Installer</strong></li>' +
      '</ol>' +
      '<button class="admin-btn admin-btn--primary admin-btn--block" data-close>Compris</button>'
    );
  });

  document.addEventListener('DOMContentLoaded', function () {
    if (isStandalone()) showButton('installed');
    else if (isIos()) showButton('ios');
    // sinon : on attend beforeinstallprompt (showButton('install'))
  });
})();
