<?php
/**
 * Paramètres boutique + zones de livraison.
 * @var array $settings
 * @var array $zones
 */
use App\Helpers\Security;
use App\Helpers\Url;
?>
<div class="admin-pagehead">
  <div>
    <h1>Paramètres</h1>
    <p>Informations boutique, contact, zones</p>
  </div>
</div>

<form method="post" action="<?= Url::admin('settings') ?>" class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Boutique</h2></div>
  <div class="admin-field">
    <label for="s-name">Nom de la boutique</label>
    <input id="s-name" name="shop_name" maxlength="60" value="<?= Security::e((string) ($settings['shop_name'] ?? '')) ?>">
  </div>
  <div class="admin-field">
    <label for="s-cur">Devise</label>
    <input id="s-cur" name="currency" maxlength="10" value="<?= Security::e((string) ($settings['currency'] ?? 'FCFA')) ?>">
  </div>
  <div class="admin-field">
    <label for="s-ship">Frais de livraison par défaut (FCFA)</label>
    <input id="s-ship" name="shipping_fee" type="number" min="0" value="<?= (int) ($settings['shipping_fee'] ?? 0) ?>">
  </div>
</form>

<form method="post" action="<?= Url::admin('settings') ?>" class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Contact</h2></div>
  <div class="admin-field">
    <label for="s-wa">WhatsApp (9 chiffres, ex. 771234567)</label>
    <input id="s-wa" name="whatsapp" maxlength="20" value="<?= Security::e((string) ($settings['whatsapp'] ?? '')) ?>" inputmode="tel">
    <small>Utilisé pour les liens de commande et de confirmation.</small>
  </div>
  <div class="admin-field">
    <label for="s-phone">Téléphone</label>
    <input id="s-phone" name="phone" maxlength="20" value="<?= Security::e((string) ($settings['phone'] ?? '')) ?>">
  </div>
  <div class="admin-field">
    <label for="s-email">Email</label>
    <input id="s-email" name="email" type="email" maxlength="120" value="<?= Security::e((string) ($settings['email'] ?? '')) ?>">
  </div>
  <div class="admin-field">
    <label for="s-addr">Adresse</label>
    <input id="s-addr" name="address" maxlength="255" value="<?= Security::e((string) ($settings['address'] ?? '')) ?>">
  </div>
  <div class="admin-field">
    <label for="s-hours">Horaires</label>
    <input id="s-hours" name="hours" maxlength="120" value="<?= Security::e((string) ($settings['hours'] ?? '')) ?>" placeholder="Ex. Lun–Sam 9h–19h">
  </div>
</form>

<form method="post" action="<?= Url::admin('settings') ?>" class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Réseaux sociaux</h2></div>
  <div class="admin-field">
    <label for="s-ig">Instagram</label>
    <input id="s-ig" name="instagram" maxlength="120" value="<?= Security::e((string) ($settings['instagram'] ?? '')) ?>">
  </div>
  <div class="admin-field">
    <label for="s-tt">TikTok</label>
    <input id="s-tt" name="tiktok" maxlength="120" value="<?= Security::e((string) ($settings['tiktok'] ?? '')) ?>">
  </div>
  <div class="admin-field">
    <label for="s-fb">Facebook</label>
    <input id="s-fb" name="facebook" maxlength="120" value="<?= Security::e((string) ($settings['facebook'] ?? '')) ?>">
  </div>
  <button class="admin-btn admin-btn--primary admin-btn--block" type="submit">Enregistrer les paramètres</button>
</form>

<!-- --------------------------------------------------------- Zones -->
<div id="zones" class="admin-card" style="scroll-margin-top:70px">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Zones de livraison</h2>
  </div>

  <?php if ($zones !== []): ?>
    <?php foreach ($zones as $z): ?>
      <form method="post" action="<?= Url::admin('settings/zones/' . (int) $z['id']) ?>" class="admin-variant">
        <input type="hidden" name="name" value="<?= Security::e((string) $z['name']) ?>">
        <div class="admin-variant__info">
          <strong><?= Security::e((string) $z['name']) ?></strong>
          <div class="admin-variant__meta">Ordre <?= (int) $z['sort_order'] ?></div>
        </div>
        <label style="font-size:.72rem;color:#6b7280">Prix
          <input type="number" name="price" min="0" value="<?= (int) $z['price'] ?>" style="width:90px;min-height:36px;padding:.3rem .5rem;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0c0c0e;color:#fff;text-align:center">
        </label>
        <label class="admin-check" style="min-height:auto;font-size:.72rem">
          <input type="checkbox" name="is_active" value="1" <?= !empty($z['is_active']) ? 'checked' : '' ?>> Active
        </label>
        <input type="hidden" name="sort_order" value="<?= (int) $z['sort_order'] ?>">
        <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">✓</button>
      </form>
      <form method="post" action="<?= Url::admin('settings/zones/' . (int) $z['id'] . '/delete') ?>" style="margin:-.4rem 0 .5rem"
            data-confirm="Supprimer la zone « <?= Security::e((string) $z['name']) ?> » ?">
        <button class="admin-btn admin-btn--danger admin-btn--sm" type="submit">Supprimer</button>
      </form>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="admin-card__sub">Aucune zone. Ajoutez-en une ci-dessous.</p>
  <?php endif; ?>

  <details style="margin-top:.8rem">
    <summary style="cursor:pointer;font-size:.82rem;color:#9ca3af;font-weight:700">+ Ajouter une zone</summary>
    <form method="post" action="<?= Url::admin('settings/zones') ?>" style="margin-top:.7rem">
      <div class="admin-field">
        <label for="z-name">Nom *</label>
        <input id="z-name" name="name" required maxlength="80" placeholder="Ex. Thiès">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <div class="admin-field">
          <label for="z-price">Prix (FCFA)</label>
          <input id="z-price" name="price" type="number" min="0" value="0">
        </div>
        <div class="admin-field">
          <label for="z-sort">Ordre</label>
          <input id="z-sort" name="sort_order" type="number" value="<?= count($zones) + 1 ?>">
        </div>
      </div>
      <label class="admin-check">
        <input type="checkbox" name="is_active" value="1" checked> Active
      </label>
      <button class="admin-btn admin-btn--primary admin-btn--sm" type="submit" style="margin-top:.5rem">Ajouter</button>
    </form>
  </details>
</div>

<!-- --------------------------------------------------------- Outils -->
<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Outils</h2>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a class="admin-btn admin-btn--dark admin-btn--sm" href="<?= Url::admin('logs') ?>">📜 Journal d'activité</a>
    <a class="admin-btn admin-btn--dark admin-btn--sm" href="<?= Url::admin('stats') ?>">📊 Statistiques</a>
    <a class="admin-btn admin-btn--dark admin-btn--sm" href="<?= Url::admin('products?sort=stock') ?>">📦 Stock faible</a>
  </div>
</div>
