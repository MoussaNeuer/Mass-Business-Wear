<?php
/**
 * Fiche client : coordonnées, cumuls, historique commandes.
 * @var array $customer
 * @var array $orders
 */
use App\Helpers\Security;
use App\Helpers\Url;

$badge = static fn (string $s): string => 'admin-badge admin-badge--' . strtolower($s);
$statusLabels = [
    'NEW' => 'Nouvelle', 'CONFIRMED' => 'Confirmée', 'PROCESSING' => 'En préparation',
    'READY' => 'Prête', 'DELIVERED' => 'Livrée', 'CANCELLED' => 'Annulée',
];
?>
<div class="admin-pagehead">
  <div>
    <h1><?= Security::e((string) $customer['name']) ?></h1>
    <p><?= Security::e((string) $customer['phone']) ?></p>
  </div>
  <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('customers') ?>">← Liste</a>
</div>

<div class="admin-stats-row">
  <div class="admin-stat">
    <div class="admin-stat__label">Commandes</div>
    <div class="admin-stat__value"><?= (int) $customer['orders_count'] ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat__label">Total dépensé</div>
    <div class="admin-stat__value admin-stat__value--sm"><?= number_format((int) $customer['total_spent'], 0, ',', ' ') ?></div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Coordonnées</h2></div>
  <div style="font-size:.84rem;color:#9ca3af;line-height:1.7">
    <div><strong style="color:#d1d5db">Téléphone :</strong> <?= Security::e((string) $customer['phone']) ?></div>
    <?php if (!empty($customer['whatsapp'])): ?>
      <div><strong style="color:#d1d5db">WhatsApp :</strong> <?= Security::e((string) $customer['whatsapp']) ?></div>
    <?php endif; ?>
    <div><strong style="color:#d1d5db">Client depuis :</strong> <?= Security::e(\App\Helpers\Format::date((string) $customer['created_at'], 'd/m/Y')) ?></div>
    <?php if (!empty($customer['first_order_at'])): ?>
      <div><strong style="color:#d1d5db">1ʳᵉ commande :</strong> <?= Security::e(\App\Helpers\Format::date((string) $customer['first_order_at'], 'd/m/Y H:i')) ?></div>
    <?php endif; ?>
    <?php if (!empty($customer['last_order_at'])): ?>
      <div><strong style="color:#d1d5db">Dernière commande :</strong> <?= Security::e(\App\Helpers\Format::date((string) $customer['last_order_at'], 'd/m/Y H:i')) ?></div>
    <?php endif; ?>
  </div>
  <?php if (!empty($customer['phone'])): ?>
    <a class="admin-btn admin-btn--dark admin-btn--sm" style="margin-top:.7rem"
       href="https://wa.me/221<?= preg_replace('/\D+/', '', (string) $customer['phone']) ?>"
       target="_blank" rel="noopener">WhatsApp client</a>
  <?php endif; ?>
</div>

<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Historique</h2>
    <span style="font-size:.75rem;color:#6b7280"><?= count($orders) ?> commande(s)</span>
  </div>

  <?php if ($orders === []): ?>
    <p class="admin-card__sub">Aucune commande.</p>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
      <a class="admin-row" href="<?= Url::admin('orders/' . (int) $o['id']) ?>">
        <div class="admin-row__body">
          <div class="admin-row__title"><?= Security::e((string) $o['reference']) ?></div>
          <div class="admin-row__meta">
            <?= Security::e(\App\Helpers\Format::date((string) $o['created_at'], 'd/m/Y H:i')) ?> ·
            <?= (int) $o['items_count'] ?> article(s)
            <?php if (!empty($o['zone'])): ?> · <?= Security::e((string) $o['zone']) ?><?php endif; ?>
          </div>
        </div>
        <div class="admin-row__end">
          <span class="<?= $badge((string) $o['status']) ?>" style="font-size:.6rem;padding:.15rem .4rem">
            <?= Security::e($statusLabels[$o['status']] ?? $o['status']) ?>
          </span>
          <div style="margin-top:.3rem;font-weight:700;color:#fff"><?= number_format((int) $o['total'], 0, ',', ' ') ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
