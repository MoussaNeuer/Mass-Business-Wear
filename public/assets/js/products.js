/**
 * MBW — Boutique : recherche rapide (debounce), filtres bottom sheet,
 * suppression de chips, chargement progressif (load more).
 */
(function () {
  'use strict';

  var ui = window.MBW.ui;

  /** Base URL publique (sous-dossier /public ou racine selon le .htaccess) */
  function base() {
    return window.MBW_BASE || '/';
  }

  /* ---------------- Recherche rapide (header) ---------------- */

  function initQuickSearch(form) {
    var input = form.querySelector('input[name="q"]');
    var results = form.querySelector('.search-results');
    if (!input || !results) return;

    var timer = null;
    var lastQ = '';

    var run = ui.debounce(function () {
      var q = input.value.trim();
      if (q === lastQ) return;
      lastQ = q;
      if (q.length < 2) {
        results.hidden = true;
        results.innerHTML = '';
        return;
      }
      fetch(base() + 'api/search?q=' + encodeURIComponent(q), {
        headers: { 'Accept': 'application/json' }
      })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          if (input.value.trim() !== q) return;
          var items = (json && json.data && json.data.items) || [];
          if (!items.length) {
            results.innerHTML = '<div class="sr-empty">Aucun résultat pour « ' + escapeHtml(q) + ' »</div>';
            results.hidden = false;
            return;
          }
          results.innerHTML = items.map(function (p) {
            return '<a href="' + escapeAttr(p.url) + '">' +
              (p.image
                ? '<img src="' + escapeAttr(p.image) + '" alt="" width="40" height="50" loading="lazy">'
                : '<span class="skeleton" style="width:40px;height:50px;border-radius:6px;display:block"></span>') +
              '<span><span class="sr-name">' + escapeHtml(p.name) + '</span><br>' +
              '<span class="sr-price">' + escapeHtml(p.price_label) + '</span></span></a>';
          }).join('');
          results.hidden = false;
        })
        .catch(function () { results.hidden = true; });
    }, 280);

    input.addEventListener('input', run);
    input.addEventListener('focus', function () {
      if (input.value.trim().length >= 2 && results.innerHTML) results.hidden = false;
    });
    document.addEventListener('click', function (e) {
      if (!form.contains(e.target)) results.hidden = true;
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { results.hidden = true; input.blur(); }
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function escapeAttr(s) { return escapeHtml(s); }

  /* ---------------- Filtres (bottom sheet) ---------------- */

  function initFilters() {
    var openBtn = document.querySelector('[data-sheet-open]');
    var sheet = document.getElementById('filter-sheet');
    var closeBtns = document.querySelectorAll('[data-sheet-close]');
    var form = document.getElementById('filter-form');
    if (!sheet || !form) return;

    if (openBtn) {
      openBtn.addEventListener('click', function () { ui.openSheet(sheet); });
    }
    closeBtns.forEach(function (b) {
      b.addEventListener('click', function () { ui.closeSheet(); });
    });

    var reset = form.querySelector('[data-filter-reset]');
    if (reset) {
      reset.addEventListener('click', function (e) {
        e.preventDefault();
        // Conserve la catégorie et la recherche, vide le reste
        form.querySelectorAll('input[type="number"]').forEach(function (i) { i.value = ''; });
        form.querySelectorAll('input[type="radio"]').forEach(function (i) { i.checked = false; });
      });
    }
  }

  /* ---------------- Load more (catalogue) ---------------- */

  function initLoadMore() {
    var btn = document.querySelector('[data-load-more]');
    var grid = document.querySelector('[data-product-grid]');
    if (!btn || !grid) return;

    var nextUrl = btn.getAttribute('data-load-more');
    if (!nextUrl) { btn.hidden = true; return; }

    btn.addEventListener('click', function () {
      if (btn.classList.contains('is-loading')) return;
      btn.classList.add('is-loading');
      var label = btn.textContent;
      btn.textContent = 'Chargement...';

      fetch(nextUrl, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          btn.classList.remove('is-loading');
          btn.textContent = label;
          if (!json.success) {
            ui.toast(json.message || 'Erreur lors du chargement', 'err');
            return;
          }
          var data = json.data || {};
          var items = data.items || [];
          items.forEach(function (p) { grid.insertAdjacentHTML('beforeend', cardHtml(p)); });
          var pag = data.pagination || {};
          var url = new URL(nextUrl, window.location.origin);
          if (pag.page && pag.page < pag.pages) {
            url.searchParams.set('page', String(pag.page + 1));
            btn.setAttribute('data-load-more', url.pathname + url.search);
          } else {
            btn.hidden = true;
          }
          if (window.MBW.cart) window.MBW.cart.syncBadges();
        })
        .catch(function () {
          btn.classList.remove('is-loading');
          btn.textContent = label;
          ui.toast('Connexion impossible. Vérifiez votre réseau.', 'err');
        });
    });
  }

  function cardHtml(p) {
    var badges = '';
    if (p.on_promo) badges += '<span class="badge-mbw badge-promo">Promo</span>';
    if (p.is_new && !p.on_promo) badges += '<span class="badge-mbw badge-new">Nouveau</span>';
    if (!p.in_stock) badges += '<span class="badge-mbw badge-out">Rupture</span>';

    return '<article class="product-card">' +
      '<a href="' + escapeAttr(p.url) + '">' +
      '<div class="product-card__media">' + badges +
      (p.image
        ? '<img src="' + escapeAttr(p.image) + '" alt="' + escapeAttr(p.name) + '" loading="lazy" width="400" height="500">'
        : '<div class="product-card__placeholder">' + escapeHtml(p.name.charAt(0)) + '</div>') +
      '</div>' +
      '<div class="product-card__body">' +
      (p.category ? '<span class="product-card__cat">' + escapeHtml(p.category) + '</span>' : '') +
      '<span class="product-card__name">' + escapeHtml(p.name) + '</span>' +
      '<div class="product-card__price">' +
      '<span class="price' + (p.on_promo ? '' : ' price--plain') + '">' + escapeHtml(p.price_label) + '</span>' +
      (p.old_price_label ? '<s class="price--old">' + escapeHtml(p.old_price_label) + '</s>' : '') +
      '</div></div></a></article>';
  }

  /* ---------------- Démarrage ---------------- */

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-quick-search]').forEach(initQuickSearch);
    initFilters();
    initLoadMore();
  });
})();
