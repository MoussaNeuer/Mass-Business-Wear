/**
 * MBW — Panier localStorage (confort uniquement).
 * Le serveur reste la seule source de vérité pour prix/stock (Phase 4).
 *
 * Format : { items: [ { id, variantId, name, slug, size, color,
 *                        price, image, qty } ], updated: ts }
 */
(function () {
  'use strict';

  var KEY = 'mbw_cart_v1';
  var MAX_QTY = 20;

  function read() {
    try {
      var raw = localStorage.getItem(KEY);
      if (!raw) return { items: [], updated: 0 };
      var data = JSON.parse(raw);
      if (!data || !Array.isArray(data.items)) return { items: [], updated: 0 };
      return data;
    } catch (e) {
      return { items: [], updated: 0 };
    }
  }

  function write(data) {
    data.updated = Date.now();
    try {
      localStorage.setItem(KEY, JSON.stringify(data));
    } catch (e) {
      /* quota / mode privé : le panier reste en mémoire de session */
    }
    document.dispatchEvent(new CustomEvent('cart:changed', { detail: data }));
  }

  function lineKey(item) {
    return item.id + '|' + (item.variantId || 0) + '|' + (item.size || '') + '|' + (item.color || '');
  }

  function count() {
    return read().items.reduce(function (n, i) { return n + i.qty; }, 0);
  }

  function total() {
    return read().items.reduce(function (n, i) { return n + i.qty * i.price; }, 0);
  }

  function items() {
    return read().items;
  }

  function add(item, qty) {
    qty = Math.min(MAX_QTY, Math.max(1, parseInt(qty, 10) || 1));
    var data = read();
    var key = lineKey(item);
    var existing = data.items.find(function (i) { return lineKey(i) === key; });
    if (existing) {
      existing.qty = Math.min(MAX_QTY, existing.qty + qty);
    } else {
      data.items.push({
        id: item.id,
        variantId: item.variantId || null,
        name: item.name,
        slug: item.slug,
        size: item.size || null,
        color: item.color || null,
        price: item.price,
        image: item.image || null,
        qty: qty
      });
    }
    write(data);
    return count();
  }

  function setQty(id, variantId, size, color, qty) {
    var data = read();
    var key = id + '|' + (variantId || 0) + '|' + (size || '') + '|' + (color || '');
    var line = data.items.find(function (i) { return lineKey(i) === key; });
    if (!line) return count();
    qty = parseInt(qty, 10) || 0;
    if (qty <= 0) {
      data.items = data.items.filter(function (i) { return lineKey(i) !== key; });
    } else {
      line.qty = Math.min(MAX_QTY, qty);
    }
    write(data);
    return count();
  }

  function remove(id, variantId, size, color) {
    return setQty(id, variantId, size, color, 0);
  }

  function clear() {
    write({ items: [], updated: Date.now() });
  }

  /** Sync des badges .cart-badge dans la page */
  function syncBadges() {
    var n = count();
    document.querySelectorAll('.cart-badge').forEach(function (el) {
      el.textContent = n > 99 ? '99+' : String(n);
      el.hidden = n === 0;
    });
  }

  function formatMoney(n, currency) {
    return new Intl.NumberFormat('fr-FR').format(n) + ' ' + (currency || 'FCFA');
  }

  /** Échappement HTML minimal pour les libellés produits. */
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /**
   * Rendu de la page panier :
   *  - #cart-empty   état vide
   *  - #cart-lines   lignes
   *  - #cart-total   total formaté
   *  - #cart-checkout masqué si vide
   * Écoute cart:changed pour rester synchro entre onglets.
   */
  var pageCurrency = 'FCFA';
  var base = (window.MBW_BASE || '/') + 'product/';

  function renderPage() {
    var empty = document.getElementById('cart-empty');
    var content = document.getElementById('cart-content');
    var linesEl = document.getElementById('cart-lines');
    var totalEl = document.getElementById('cart-total');
    var checkoutEl = document.getElementById('cart-checkout');
    if (!empty || !content || !linesEl) return;

    var list = items();
    if (!list.length) {
      empty.hidden = false;
      content.hidden = true;
      if (checkoutEl) checkoutEl.hidden = true;
      return;
    }

    empty.hidden = true;
    content.hidden = false;
    if (checkoutEl) checkoutEl.hidden = false;
    if (totalEl) totalEl.textContent = formatMoney(total(), pageCurrency);

    linesEl.innerHTML = list.map(function (i) {
      var key = [i.id, i.variantId || 0, i.size || '', i.color || ''].join('|');
      var meta = [i.size, i.color].filter(Boolean).join(' · ');
      return '<div class="cart-line card-mbw mb-2" data-key="' + esc(key) + '">' +
        (i.image
          ? '<img class="cart-line__img" src="' + esc(i.image) + '" alt="" width="64" height="80" loading="lazy">'
          : '<div class="skeleton cart-line__img"></div>') +
        '<div class="cart-line__main">' +
        '<a class="cart-line__name" href="' + base + esc(i.slug) + '">' + esc(i.name) + '</a>' +
        (meta ? '<div class="cart-line__meta">' + esc(meta) + '</div>' : '') +
        '<div class="cart-line__price">' + esc(formatMoney(i.price, pageCurrency)) + '</div>' +
        '</div>' +
        '<div class="cart-line__side">' +
        '<div class="qty-stepper qty-stepper--sm">' +
        '<button type="button" data-act="dec" aria-label="Diminuer la quantité">−</button>' +
        '<span aria-live="polite">' + i.qty + '</span>' +
        '<button type="button" data-act="inc" aria-label="Augmenter la quantité">+</button>' +
        '</div>' +
        '<button type="button" class="cart-line__del" data-act="del" aria-label="Supprimer ' + esc(i.name) + '">✕</button>' +
        '</div>' +
        '</div>';
    }).join('');
  }

  /** Bind une fois sur la page panier (délégation). */
  function bindPage() {
    var linesEl = document.getElementById('cart-lines');
    if (!linesEl) return;
    var clearBtn = document.getElementById('cart-clear');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        if (window.confirm('Vider tout votre panier ?')) {
          clear();
          uiToast('Panier vidé');
        }
      });
    }

    linesEl.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-act]');
      if (!btn) return;
      var row = btn.closest('[data-key]');
      if (!row) return;
      var parts = row.getAttribute('data-key').split('|');
      var id = parseInt(parts[0], 10);
      var variantId = parts[1] !== '0' ? parseInt(parts[1], 10) : null;
      var size = parts[2] || null;
      var color = parts[3] || null;
      var line = items().find(function (i) {
        return i.id === id && (i.variantId || null) === variantId &&
          (i.size || null) === size && (i.color || null) === color;
      });
      if (!line) return;

      var act = btn.getAttribute('data-act');
      if (act === 'inc') setQty(id, variantId, size, color, line.qty + 1);
      else if (act === 'dec') setQty(id, variantId, size, color, line.qty - 1);
      else if (act === 'del') { remove(id, variantId, size, color); uiToast('Article retiré'); }
    });
  }

  function uiToast(msg) {
    if (window.MBW && window.MBW.ui) window.MBW.ui.toast(msg, 'ok');
  }

  document.addEventListener('cart:changed', function () {
    renderPage();
    syncBadges();
  });
  document.addEventListener('DOMContentLoaded', function () {
    syncBadges();
    bindPage();
    renderPage();
  });
  window.addEventListener('storage', function (e) {
    if (e.key === KEY) syncBadges();
  });

  window.MBW = window.MBW || {};
  window.MBW.cart = {
    add: add,
    remove: remove,
    setQty: setQty,
    clear: clear,
    count: count,
    total: total,
    items: items,
    formatMoney: formatMoney,
    syncBadges: syncBadges,
    renderPage: renderPage,
    bindPage: bindPage,
    setPageCurrency: function (c) { pageCurrency = c; }
  };
})();
