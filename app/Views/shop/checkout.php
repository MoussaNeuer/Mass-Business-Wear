<?php
/**
 * Checkout — Phase 3 : formulaire (nom, téléphone, zone, adresse, note)
 * + récapitulatif panier. La validation serveur arrive en Phase 4.
 *
 * @var string $currency
 * @var string $whatsapp
 * @var array  $shippingZones  id,name,price
 */
use App\Helpers\Security;
use App\Helpers\Url;

$currency = $currency ?? 'FCFA';
?>
<section class="mt-4" id="checkout-page" data-currency="<?= Security::e($currency) ?>">
  <h1 style="font-size:1.5rem;font-weight:800">Finaliser la commande</h1>
  <p class="text-muted" style="font-size:.9rem">Remplissez vos coordonnées, nous vous confirmons sur WhatsApp.</p>

  <div class="empty-state mt-3" id="checkout-empty">
    <div class="icon" aria-hidden="true">🛒</div>
    <h3>Votre panier est vide</h3>
    <p>Ajoutez des articles avant de passer commande.</p>
    <a class="btn-mbw btn-primary" href="<?= Url::to('shop') ?>">Découvrir la boutique</a>
  </div>

  <div id="checkout-content" hidden>
    <form id="checkout-form" class="mt-3" novalidate>
      <div class="field mb-3">
        <label for="co-name">Nom complet <span class="text-red">*</span></label>
        <input id="co-name" name="name" type="text" required autocomplete="name" placeholder="Ex. Amina Diop">
      </div>

      <div class="field mb-3">
        <label for="co-phone">Téléphone <span class="text-red">*</span></label>
        <input id="co-phone" name="phone" type="tel" required autocomplete="tel" inputmode="tel"
               pattern="7[0-9]{7}" placeholder="Ex. 771234567" maxlength="9">
        <small class="text-muted">9 chiffres, commence par 7 (Sénégal)</small>
      </div>

      <div class="field mb-3">
        <label for="co-zone">Zone de livraison <span class="text-red">*</span></label>
        <select id="co-zone" name="zone" required>
          <?php foreach ($shippingZones as $z): ?>
            <option value="<?= (int) $z['id'] ?>" data-price="<?= (int) $z['price'] ?>">
              <?= Security::e($z['name']) ?> — <?= number_format((int) $z['price'], 0, ',', ' ') ?> <?= Security::e($currency) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field mb-3">
        <label for="co-address">Adresse / repère <span class="text-red">*</span></label>
        <input id="co-address" name="address" type="text" required autocomplete="street-address"
               placeholder="Quartier, rue, point de repère…">
      </div>

      <div class="field mb-3">
        <label for="co-note">Note (optionnel)</label>
        <textarea id="co-note" name="note" rows="2" placeholder="Instructions particulières…"></textarea>
      </div>
    </form>

    <div class="card-mbw mt-3">
      <h3 style="font-size:.95rem;font-weight:700;margin:0 0 .5rem">Récapitulatif</h3>
      <div id="checkout-lines"></div>
      <div class="co-row" style="display:flex;justify-content:space-between;padding:.4rem 0">
        <span class="text-muted">Sous-total</span>
        <span id="co-subtotal">—</span>
      </div>
      <div class="co-row" style="display:flex;justify-content:space-between;padding:.4rem 0">
        <span class="text-muted">Livraison</span>
        <span id="co-shipping">—</span>
      </div>
      <div class="co-row" style="display:flex;justify-content:space-between;padding:.6rem 0;border-top:1px solid var(--border);font-weight:800">
        <span>Total</span>
        <span id="co-total">—</span>
      </div>
    </div>

    <div class="alert-mbw alert-success mt-3" style="font-size:.85rem">
      Prix et stocks sont revérifiés côté serveur. En cas de rupture, nous vous prévenons sur WhatsApp.
    </div>

    <div id="co-error" class="alert-mbw alert-error mt-3" hidden></div>

    <button type="button" id="co-submit" class="btn-mbw btn-primary btn-block mt-2" disabled>
      Valider la commande
    </button>
    <a class="btn-mbw btn-ghost btn-block mt-2" href="<?= Url::to('cart') ?>">← Retour au panier</a>
  </div>
</section>

<script>
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var cart = window.MBW && window.MBW.cart;
    var ui = window.MBW && window.MBW.ui;
    if (!cart) return;

    var page = document.getElementById('checkout-page');
    var cur = (page && page.dataset.currency) || 'FCFA';
    var empty = document.getElementById('checkout-empty');
    var content = document.getElementById('checkout-content');
    var linesEl = document.getElementById('checkout-lines');
    var zoneEl = document.getElementById('co-zone');
    var submitBtn = document.getElementById('co-submit');
    var errorBox = document.getElementById('co-error');
    var form = document.getElementById('checkout-form');
    var busy = false;

    // Clé d'idempotence : même onglet / retry réseau → pas de double commande
    var idemKey = (function () {
      try {
        var k = sessionStorage.getItem('mbw_idem');
        if (!k) {
          k = (crypto.randomUUID ? crypto.randomUUID()
            : Date.now().toString(36) + Math.random().toString(36).slice(2));
          sessionStorage.setItem('mbw_idem', k);
        }
        return k;
      } catch (e) {
        return Date.now().toString(36) + Math.random().toString(36).slice(2);
      }
    })();

    function fmt(n) { return cart.formatMoney(n, cur); }

    function showError(msg) {
      if (!errorBox) return;
      errorBox.textContent = msg;
      errorBox.hidden = !msg;
    }

    function zoneLabel() {
      return zoneEl && zoneEl.selectedOptions.length ? zoneEl.selectedOptions[0].textContent : '';
    }
    function zoneId() {
      return zoneEl ? zoneEl.value : '';
    }

    function render() {
      var items = cart.items();
      if (!items.length) {
        empty.hidden = false;
        content.hidden = true;
        return;
      }
      empty.hidden = true;
      content.hidden = false;

      linesEl.innerHTML = items.map(function (i) {
        var meta = [i.size, i.color].filter(Boolean).join(' · ');
        return '<div class="co-item" style="display:flex;justify-content:space-between;gap:.5rem;padding:.35rem 0;font-size:.9rem">' +
          '<span>' + i.name + (meta ? ' <span class="text-muted">(' + meta + ')</span>' : '') +
          ' <span class="text-muted">×' + i.qty + '</span></span>' +
          '<span style="font-weight:700;white-space:nowrap">' + fmt(i.price * i.qty) + '</span></div>';
      }).join('');

      var sub = cart.total();
      var ship = zoneEl ? parseInt(zoneEl.selectedOptions[0].dataset.price, 10) || 0 : 0;
      document.getElementById('co-subtotal').textContent = fmt(sub);
      document.getElementById('co-shipping').textContent = fmt(ship);
      document.getElementById('co-total').textContent = fmt(sub + ship);

      if (submitBtn) submitBtn.disabled = busy;
    }

    function validateForm() {
      if (!form) return false;
      var name = form.querySelector('#co-name');
      var phone = form.querySelector('#co-phone');
      var address = form.querySelector('#co-address');
      if (!name.value.trim()) { showError('Merci d\'indiquer votre nom.'); name.focus(); return false; }
      if (!/^7[0-9]{8}$/.test(phone.value.trim())) {
        showError('Téléphone invalide : 9 chiffres, commence par 7 (ex. 771234567).');
        phone.focus(); return false;
      }
      if (!address.value.trim()) { showError('Merci d\'indiquer votre adresse / repère.'); address.focus(); return false; }
      if (!zoneEl.value) { showError('Choisissez une zone de livraison.'); zoneEl.focus(); return false; }
      showError('');
      return true;
    }

    function collectPayload() {
      var items = cart.items().map(function (i) {
        return {
          product_id: i.id,
          variant_id: i.variantId || null,
          quantity: i.qty
        };
      });
      return {
        name: form.querySelector('#co-name').value.trim(),
        phone: form.querySelector('#co-phone').value.trim(),
        zone: zoneId(),
        address: form.querySelector('#co-address').value.trim(),
        note: form.querySelector('#co-note') ? form.querySelector('#co-note').value.trim() : '',
        items: items,
        idempotency_key: idemKey
      };
    }

    if (submitBtn) {
      submitBtn.addEventListener('click', function () {
        if (busy) return;
        if (!validateForm()) return;

        busy = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi…';
        showError('');

        var csrf = (ui && ui.csrfToken) ? ui.csrfToken() : '';
        var base = (window.MBW_BASE || '/') + 'api/orders';

        fetch(base, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf,
            'X-Idempotency-Key': idemKey,
            'Accept': 'application/json'
          },
          body: JSON.stringify(collectPayload())
        }).then(function (r) {
          return r.json().then(function (body) {
            return { ok: r.ok, status: r.status, body: body };
          });
        }).then(function (res) {
          busy = false;
          if (!res.ok || !res.body.success) {
            showError((res.body && res.body.message) || 'Erreur serveur. Réessayez.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Valider la commande';
            if (ui) ui.toast('Commande refusée', 'err');
            return;
          }
          // Succès : vider panier + idem key, rediriger
          try {
            cart.clear();
            sessionStorage.removeItem('mbw_idem');
          } catch (e) { /* ignore */ }
          var ref = res.body.data && res.body.data.reference;
          var target = (window.MBW_BASE || '/') + 'order/success' + (ref ? '?ref=' + encodeURIComponent(ref) : '');
          window.location.href = target;
        }).catch(function (err) {
          busy = false;
          submitBtn.disabled = false;
          submitBtn.textContent = 'Valider la commande';
          showError('Connexion impossible. Vérifiez votre réseau et réessayez.');
          if (ui) ui.toast('Erreur réseau', 'err');
          if (window.console) console.error(err);
        });
      });
    }

    if (zoneEl) zoneEl.addEventListener('change', render);
    document.addEventListener('cart:changed', render);
    render();
  });
})();
</script>
