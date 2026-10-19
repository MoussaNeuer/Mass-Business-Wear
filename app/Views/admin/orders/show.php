<?php
/**
 * Détail d'une commande admin : articles, client, changement statut, historique.
 * @var array $order
 * @var array $items
 * @var array $history
 * @var array $statusLabels
 * @var array $allowedStatuses
 * @var string $currency
 * @var string $whatsappNumber
 */
use App\Helpers\Security;
use App\Helpers\Url;

$badge = static fn (string $s): string => 'admin-badge admin-badge--' . strtolower($s);
$statusOptions = array_filter(
    [
        'NEW' => 'Nouvelle',
        'CONFIRMED' => 'Confirmée',
        'PROCESSING' => 'En préparation',
        'READY' => 'Prête',
        'DELIVERED' => 'Livrée',
        'CANCELLED' => 'Annulée (restock)',
    ],
    static fn (string $val): bool => in_array($val, $allowedStatuses ?? [], true),
    ARRAY_FILTER_USE_KEY
);
$isArchived = !empty($order['archived_at']);
$phoneRaw = preg_replace('/\D+/', '', (string) $order['customer_phone']) ?? '';
$phoneIntl = ($phoneRaw !== '' && !str_starts_with($phoneRaw, '221')) ? '221' . $phoneRaw : $phoneRaw;
$waText = 'Bonjour ' . $order['customer_name'] . ', au sujet de votre commande '
    . $order['reference'] . ' (' . number_format((int) $order['total'], 0, ',', ' ') . ' FCFA).';
$waUrl = $phoneIntl !== ''
    ? 'https://wa.me/' . $phoneIntl . '?text=' . rawurlencode($waText)
    : '';
$canConfirm = in_array('CONFIRMED', $allowedStatuses ?? [], true);
?>
<div class="admin-pagehead">
  <div>
    <h1><?= Security::e((string) $order['reference']) ?><?= $isArchived ? ' 📦' : '' ?></h1>
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

<?php if (!$isArchived): ?>
<div class="admin-card admin-order-actions" style="gap:.5rem;flex-wrap:wrap">
  <?php if ($waUrl !== ''): ?>
    <a class="admin-btn admin-btn--sm admin-btn--wa" href="<?= Security::e($waUrl) ?>" target="_blank" rel="noopener" style="flex:1">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.5 14.1c-.2.7-1.3 1.3-2.1 1.4-.6.1-1.3.1-2.1-.1a12 12 0 0 1-6.5-5.7c-.6-1.1-.6-2.2 0-3.3.2-.4.6-.9 1-.9h.7c.2 0 .5-.1.8.6l.9 2.1c.1.2.1.4 0 .6l-.4.6c-.1.2-.3.4-.1.7a9 9 0 0 0 3.8 3.4c.3.1.5.1.7-.1l.9-1c.2-.2.4-.3.7-.2l2.1 1c.3.1.5.3.6.4.1.2.1.6-.1 1.1z"/></svg>
      WhatsApp
    </a>
  <?php endif; ?>
  <a class="admin-btn admin-btn--sm admin-btn--dark" href="tel:<?= Security::e($phoneIntl) ?>" style="flex:1">📞 Appeler</a>
  <?php if ($canConfirm): ?>
    <form method="post" action="<?= Url::admin('orders/' . (int) $order['id'] . '/confirm') ?>" style="margin:0;flex:1">
      <?= Security::csrfField() ?>
      <button class="admin-btn admin-btn--sm admin-btn--primary admin-btn--block" type="submit">✓ Confirmer</button>
    </form>
  <?php endif; ?>
</div>
<?php endif; ?>

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

<?php if ($statusOptions !== [] && !$isArchived): ?>
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
      <small class="text-muted">Seules les transitions autorisées sont proposées.</small>
    </div>
    <div class="admin-field">
      <label for="o-note">Note (optionnel)</label>
      <input id="o-note" name="note" maxlength="255" placeholder="Ex. Client prévenu par téléphone">
    </div>
    <button class="admin-btn admin-btn--primary admin-btn--block" type="submit">Mettre à jour</button>
  </form>
</div>
<?php endif; ?>

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
  <div class="admin-card__head"><h2 class="admin-card__title" style="color:#f87171">Zone sensible</h2></div>
  <?php if ($isArchived): ?>
    <p class="admin-card__sub">Commande archivée : masquée des listes, historique et CA conservés.</p>
    <form method="post" action="<?= Url::admin('orders/' . (int) $order['id'] . '/unarchive') ?>">
      <?= Security::csrfField() ?>
      <button class="admin-btn admin-btn--ghost admin-btn--sm" type="submit">↩ Restaurer la commande</button>
    </form>
  <?php else: ?>
    <p class="admin-card__sub">L'archivage retire la commande des vues actives sans effacer l'historique ni le chiffre d'affaires. Aucune suppression définitive n'est possible.</p>
    <form method="post" action="<?= Url::admin('orders/' . (int) $order['id'] . '/archive') ?>"
          data-confirm="Archiver la commande <?= Security::e((string) $order['reference']) ?> ?">
      <?= Security::csrfField() ?>
      <button class="admin-btn admin-btn--danger admin-btn--sm" type="submit">📦 Archiver la commande</button>
    </form>
  <?php endif; ?>
</div>
