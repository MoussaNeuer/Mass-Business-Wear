<?php
/**
 * Panier — Phase 3 : rendu réactif depuis localStorage (cart.js),
 * lignes avec quantités / suppression, total, CTA "Commander".
 */
use App\Helpers\Url;

$currency = $currency ?? 'FCFA';
?>
<section class="mt-4" id="cart-page" data-currency="<?= \App\Helpers\Security::e($currency) ?>">
  <h1 style="font-size:1.5rem;font-weight:800">Mon panier</h1>

  <div class="empty-state mt-3" id="cart-empty">
    <div class="icon" aria-hidden="true">🛒</div>
    <h3>Votre panier est vide</h3>
    <p>Parcourez la boutique et ajoutez vos coups de cœur. Votre panier est conservé sur cet appareil.</p>
    <a class="btn-mbw btn-primary" href="<?= Url::to('shop') ?>">Découvrir la boutique</a>
  </div>

  <div id="cart-content" hidden>
    <div id="cart-lines" aria-live="polite"></div>

    <div class="card-mbw mt-3">
      <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1.1rem">
        <span>Total</span>
        <span id="cart-total">—</span>
      </div>
      <p class="text-muted" style="font-size:.8rem;margin:.5rem 0 0">
        Prix et stocks seront revérifiés côté serveur lors de la validation.
      </p>
      <div id="cart-checkout" class="mt-3" hidden>
        <a class="btn-mbw btn-primary btn-block" href="<?= Url::to('checkout') ?>">Passer commande</a>
      </div>
      <a class="btn-mbw btn-ghost btn-block mt-2" href="<?= Url::to('shop') ?>">Continuer mes achats</a>
      <button type="button" id="cart-clear" class="btn-mbw btn-ghost btn-block mt-2" style="color:var(--mbw-red)">Vider le panier</button>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var cart = window.MBW && window.MBW.cart;
    if (!cart) return;
    var page = document.getElementById('cart-page');
    if (page && page.dataset.currency) cart.setPageCurrency(page.dataset.currency);
  });
</script>
