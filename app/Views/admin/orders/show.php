<?php
/**
 * Détail d'une commande admin : articles, client, changement statut, historique.
 * @var array $order
 * @var array $items
 * @var array $history
 * @var array $statusLabels
 * @var string $currency
 */
use App\Helpers\Security;
use App\Helpers\Url;

$badge = static fn (string $s): string => 'admin-badge admin-badge--' . strtolower($s);
$statusOptions = [
    'NEW' => 'Nouvelle',
    'CONFIRMED' => 'Confirmée',
    'PROCESSING' => 'En préparation',
    'READY' => 'Prête',
    'DELIVERED' => 'Livrée',
    'CANCELLED' => 'Annulée (restock)',
];
?>
<div class="admin-pagehead">
  <div>
    <h1><?= Security::e((string) $order['reference']) ?></h1>
    <p><?= Security::e(\App\Helpers\Format::date((string) $order['created_at'], 'd/m/Y H:i')) ?></p>
  </div>
  <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('orders') ?>">← Liste</a>
</div>

<div class="admin-card">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.6rem">
    <span class="<?= $badge((string) $order['status']) ?>"><?= Security::e($statusLabels[$order['status']] ?? $order['status']) ?></span>
    <strong style="color:#fff;font-size:1.05rem"><?= number_format((int) $order['total'], 0, ',', ' ') ?> <?= Security::e($currency) ?></strong>
  </div>

  <div style="font-size:.84rem;color:#9ca3af;line-height:1.6">
    <div><strong style="color:#d1d5db">Client :</strong> <?= Security::e((string) $order['customer_name']) ?></div>
    <div><strong style="color:#d1d5db">Téléphone :</strong> <?= Security::e((string) $order['customer_phone']) ?></div>
    <div><strong style="color:#d1d5db">Zone :</strong> <?= Security::e((string) $order['zone']) ?></div>
    <div><strong style="color:#d1d5db">Adresse :</strong> <?= Security::e((string) $order['address']) ?></div>
    <?php if (!empty($order['notes'])): ?>
      <div><strong style="color:#d1d5db">Note :</strong> <?= Security::e((string) $order['notes']) ?></div>
    <?php endif; ?>
    <div style="margin-top:.3rem">
      Sous-total <?= number_format((int) $order['subtotal'], 0, ',', ' ') ?> +
      Livraison <?= number_format((int) $order['shipping'], 0, ',', ' ') ?> =
      <strong style="color:#fff">Total <?= number_format((int) $order['total'], 0, ',', ' ') ?></strong>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Articles</h2></div>
  <?php foreach ($items as $it): ?>
    <div class="admin-row" style="cursor:default">
      <div class="admin-row__body">
        <div class="admin-row__title"><?= Security::e((string) $it['product_name']) ?></div>
        <div class="admin-row__meta">
          <?php $meta = trim(($it['size'] ?? '') . ' ' . ($it['color'] ?? '')); if ($meta !== ''): ?>
            <?= Security::e($meta) ?> ·
          <?php endif; ?>
          <?= number_format((int) $it['unit_price'], 0, ',', ' ') ?> × <?= (int) $it['quantity'] ?>
        </div>
      </div>
      <div class="admin-row__end"><?= number_format((int) $it['unit_price'] * (int) $it['quantity'], 0, ',', ' ') ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Changer le statut</h2></div>
  <form method="post" action="<?= Url::admin('orders/' . (int) $order['id'] . '/status') ?>">
    <div class="admin-field">
      <label for="o-status">Nouveau statut</label>
      <select id="o-status" name="status" required>
        <?php foreach ($statusOptions as $val => $label): ?>
          <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>><?= Security::e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="admin-field">
      <label for="o-note">Note (optionnel)</label>
      <input id="o-note" name="note" maxlength="255" placeholder="Ex. Client prévenu par téléphone">
    </div>
    <button class="admin-btn admin-btn--primary admin-btn--block" type="submit">Mettre à jour</button>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Historique</h2></div>
  <?php if ($history === []): ?>
    <p class="admin-card__sub">Aucun historique.</p>
  <?php else: ?>
    <ul class="admin-timeline">
      <?php foreach ($history as $h): ?>
        <li>
          <div style="font-weight:600;color:#fff;font-size:.84rem">
            <?= Security::e($statusLabels[$h['to_status']] ?? $h['to_status']) ?>
            <?php if (!empty($h['from_status'])): ?>
              <span style="color:#6b7280;font-weight:400">← <?= Security::e($statusLabels[$h['from_status']] ?? $h['from_status']) ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($h['note'])): ?><div style="font-size:.78rem;color:#9ca3af"><?= Security::e((string) $h['note']) ?></div><?php endif; ?>
          <div class="admin-timeline__time">
            <?= Security::e(\App\Helpers\Format::date((string) $h['created_at'], 'd/m/Y H:i')) ?>
            <?php if (!empty($h['admin_login'])): ?>
              <span class="admin-timeline__who"> · par <?= Security::e((string) $h['admin_login']) ?></span>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<div class="admin-card" style="border-color:rgba(212,0,0,.2)">
  <div class="admin-card__head"><h2 class="admin-card__title" style="color:#f87171">Zone dangereuse</h2></div>
  <form method="post" action="<?= Url::admin('orders/' . (int) $order['id'] . '/delete') ?>"
        data-confirm="Supprimer définitivement la commande <?= Security::e((string) $order['reference']) ?> ?">
    <button class="admin-btn admin-btn--danger admin-btn--sm" type="submit">Supprimer la commande</button>
  </form>
</div>
