/**
 * MBW — Fiche produit (PDP) : galerie swipeable, sélection des variantes
 * (taille × couleur avec filtrage croisé), quantité, ajout au panier,
 * partage natif (Web Share API) avec repli copie du lien.
 *
 * Données : <script type="application/json" id="pdp-data">…</script>
 */
(function () {
  'use strict';

  var dataEl = document.getElementById('pdp-data');
  if (!dataEl) return;

  var ui = window.MBW.ui;
  var cart = window.MBW.cart;
  var data;
  try {
    data = JSON.parse(dataEl.textContent);
  } catch (e) {
    return;
  }

  var variants = data.variants || [];
  var state = { size: null, color: null, qty: 1 };

  /* ---------------- Galerie swipe + dots ---------------- */

  var strip = document.querySelector('[data-gallery-strip]');
  var dotsWrap = document.querySelector('[data-gallery-dots]');

  if (strip && dotsWrap && strip.children.length > 1) {
    var slides = strip.children.length;
    var current = 0;

    strip.addEventListener('scroll', ui.debounce(function () {
      var idx = Math.round(strip.scrollLeft / strip.clientWidth);
      if (idx !== current && idx >= 0 && idx < slides) {
        current = idx;
        updateDots();
      }
    }, 80), { passive: true });

    function updateDots() {
      for (var i = 0; i < dotsWrap.children.length; i++) {
        dotsWrap.children[i].classList.toggle('is-active', i === current);
      }
    }
  }

  /* ---------------- Sélection des variantes ---------------- */

  function optionValues(key) {
    var seen = {};
    var out = [];
    variants.forEach(function (v) {
      var val = v[key];
      if (val && !seen[val]) {
        seen[val] = true;
        out.push(val);
      }
    });
    return out;
  }

  /** Combinaisons disponibles selon la sélection courante (l'autre axe). */
  function availableValues(selectedKey, valueKey) {
    return variants.filter(function (v) {
      if (v.stock <= 0) return false;
      var otherKey = selectedKey === 'size' ? 'color' : 'size';
      return state[otherKey] === null || v[otherKey] === state[otherKey];
    }).map(function (v) { return v[valueKey]; });
  }

  function findVariant() {
    if (!variants.length) {
      // Produit sans variantes : vendable tel quel, stock = stock produit
      return { id: null, size: null, color: null, price_override: null, stock: data.stock | 0 };
    }
    return variants.find(function (v) {
      return (data.hasSize ? v.size === state.size : true) &&
        (data.hasColor ? v.color === state.color : true);
    }) || null;
  }

  function renderPills() {
    ['size', 'color'].forEach(function (key) {
      var box = document.querySelector('[data-variants="' + key + '"]');
      if (!box) return;
      var enabled = availableValues(key, key);
      var pills = box.querySelectorAll('.variant-pill');
      pills.forEach(function (pill) {
        var val = pill.getAttribute('data-value');
        var ok = enabled.indexOf(val) !== -1;
        pill.classList.toggle('is-disabled', !ok);
        pill.classList.toggle('is-selected', state[key] === val);
        pill.setAttribute('aria-pressed', state[key] === val ? 'true' : 'false');
        var input = pill.querySelector('input');
        if (input) {
          input.disabled = !ok;
          input.checked = state[key] === val;
        }
      });
    });
  }

  function refresh() {
    renderPills();
    var v = findVariant();
    var priceEl = document.querySelector('[data-pdp-price]');
    var stockEl = document.querySelector('[data-pdp-stock]');
    var cta = document.querySelector('[data-add-to-cart]');
    var qtyMaxInput = document.querySelector('[data-qty]');

    var ready = v !== null && v.stock > 0;
    var price = ready && v.price_override != null ? v.price_override : data.price;
    var stock = ready ? v.stock : 0;

    if (priceEl) {
      priceEl.innerHTML = formatMoney(price) +
        (data.oldPrice ? ' <s class="price--old">' + formatMoney(data.oldPrice) + '</s>' : '');
    }

    if (stockEl) {
      if (!ready && data.hasSize && data.hasColor && state.size && state.color) {
        stockEl.textContent = 'Combinaison épuisée';
        stockEl.className = 'text-muted';
      } else if (ready && stock <= 3) {
        stockEl.textContent = 'Plus que ' + stock + ' en stock';
        stockEl.className = 'text-red';
      } else if (ready) {
        stockEl.textContent = 'En stock';
        stockEl.className = 'text-muted';
      } else {
        stockEl.textContent = '';
      }
    }

    if (qtyMaxInput) {
      qtyMaxInput.max = String(Math.min(stock || 1, 20));
      if (state.qty > (stock || 1)) {
        state.qty = Math.min(stock, 20) || 1;
        qtyMaxInput.value = String(state.qty);
      }
    }

    if (cta) {
      cta.disabled = !ready;
      cta.setAttribute('aria-disabled', ready ? 'false' : 'true');
      var label = cta.querySelector('[data-cta-label]');
      if (label) {
        if (!ready && data.hasSize && !state.size) {
          label.textContent = 'Choisissez une taille';
        } else if (!ready && data.hasColor && !state.color) {
          label.textContent = 'Choisissez une couleur';
        } else if (!ready) {
          label.textContent = 'Épuisé';
        } else {
          label.textContent = 'Ajouter au panier';
        }
      }
    }

    // Lien WhatsApp pré-rempli avec la sélection courante
    var wa = document.querySelector('[data-wa-order]');
    if (wa && data.whatsapp) {
      var text = data.name + (ready
        ? ' — Taille: ' + (state.size || '-') + ', Couleur: ' + (state.color || '-') + ', Qté: ' + state.qty
        : '');
      wa.href = 'https://wa.me/221' + data.whatsapp + '?text=' + encodeURIComponent(text + ' | ' + data.shareUrl);
    }
  }

  function formatMoney(n) {
    return new Intl.NumberFormat('fr-FR').format(n) + ' ' + (data.currency || 'FCFA');
  }

  // Clics sur les pills
  document.querySelectorAll('[data-variants]').forEach(function (box) {
    var key = box.getAttribute('data-variants');
    box.addEventListener('click', function (e) {
      var pill = e.target.closest('.variant-pill');
      if (!pill || pill.classList.contains('is-disabled')) return;
      var val = pill.getAttribute('data-value');
      state[key] = state[key] === val ? null : val;
      refresh();
    });
  });

  /* ---------------- Quantité ---------------- */

  var qtyWrap = document.querySelector('[data-qty-wrap]');
  if (qtyWrap) {
    qtyWrap.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-qty-act]');
      if (!btn) return;
      var input = qtyWrap.querySelector('[data-qty]');
      var max = parseInt(input.max, 10) || 20;
      var next = state.qty + (btn.getAttribute('data-qty-act') === 'inc' ? 1 : -1);
      state.qty = Math.max(1, Math.min(max, next));
      input.value = String(state.qty);
    });
    var qtyInput = qtyWrap.querySelector('[data-qty]');
    if (qtyInput) {
      qtyInput.addEventListener('change', function () {
        var max = parseInt(qtyInput.max, 10) || 20;
        state.qty = Math.max(1, Math.min(max, parseInt(qtyInput.value, 10) || 1));
        qtyInput.value = String(state.qty);
        refresh();
      });
    }
  }

  /* ---------------- Ajout au panier ---------------- */

  var addBtn = document.querySelector('[data-add-to-cart]');
  if (addBtn && cart) {
    addBtn.addEventListener('click', function () {
      var v = findVariant();
      if (!v || v.stock <= 0) return;

      cart.add({
        id: data.id,
        variantId: v.id,
        name: data.name,
        slug: data.slug,
        size: v.size,
        color: v.color,
        price: v.price_override != null ? v.price_override : data.price,
        image: data.image
      }, state.qty);

      ui.toast('Ajouté au panier (' + state.qty + ')', 'ok');
      addBtn.classList.add('is-loading');
      setTimeout(function () { addBtn.classList.remove('is-loading'); }, 600);
    });
  }

  /* ---------------- Partage ---------------- */

  var shareBtn = document.querySelector('[data-share]');
  if (shareBtn) {
    shareBtn.addEventListener('click', function () {
      var payload = { title: data.name, text: data.name + ' — MASS BUSINESS WEAR', url: data.shareUrl };
      if (navigator.share) {
        navigator.share(payload).catch(function () { /* annulé */ });
      } else if (navigator.clipboard) {
        navigator.clipboard.writeText(data.shareUrl).then(function () {
          ui.toast('Lien copié', 'ok');
        });
      } else {
        ui.toast('Copiez le lien depuis la barre d\'adresse', 'err');
      }
    });
  }

  var copyBtn = document.querySelector('[data-copy-link]');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(data.shareUrl).then(function () { ui.toast('Lien copié', 'ok'); });
      } else {
        ui.toast('Copiez le lien depuis la barre d\'adresse', 'err');
      }
    });
  }

  refresh();
})();
