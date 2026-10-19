<?php
/**
 * Dashboard admin — stats, commandes récentes, alertes stock.
 * @var array $stats
 * @var array $recentOrders
 * @var array $lowStock
 * @var array $statusCounts
 * @var array $revenueWeek
 * @var array $topProducts
 * @var string $adminLogin
 */
use App\Helpers\Security;
use App\Helpers\Url;
use App\Repositories\AdminOrderRepository;

$cur = 'FCFA';
$fmt = static fn (int $n): string => number_format($n, 0, ',', ' ') . ' ' . $cur;
$badge = static fn (string $s): string => 'admin-badge admin-badge--' . strtolower($s);
?>
<div class="admin-pagehead">
  <div>
    <h1>Bonjour, <?= Security::e($adminLogin) ?></h1>
    <p>Vue d'ensemble de la boutique</p>
  </div>
  <div style="display:flex;gap:.4rem;flex-wrap:wrap">
    <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('stats') ?>">Stats</a>
    <a class="admin-btn admin-btn--primary admin-btn--sm" href="<?= Url::admin('products/create') ?>">+ Produit</a>
  </div>
</div>

<div class="admin-stats">
  <a class="admin-stat" href="<?= Url::admin('orders') ?>">
    <div class="admin-stat__value admin-stat__value--red"><?= (int) $stats['pending_orders'] ?></div>
    <div class="admin-stat__label">En attente</div>
  </a>
  <a class="admin-stat" href="<?= Url::admin('orders') ?>">
    <div class="admin-stat__value admin-stat__value--green"><?= (int) $stats['today_orders'] ?></div>
    <div class="admin-stat__label">Aujourd'hui</div>
  </a>
  <a class="admin-stat" href="<?= Url::admin('products') ?>">
    <div class="admin-stat__value"><?= (int) $stats['active_products'] ?><span style="font-size:.7rem;color:#6b7280"> / <?= (int) $stats['products'] ?></span></div>
    <div class="admin-stat__label">Produits actifs</div>
  </a>
  <a class="admin-stat" href="<?= Url::admin('customers') ?>">
    <div class="admin-stat__value"><?= (int) $stats['customers'] ?></div>
    <div class="admin-stat__label">Clients</div>
  </a>
</div>

<div class="admin-card" style="margin-top:.85rem">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Chiffre d'affaires</h2>
    <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('stats') ?>">Détails →</a>
  </div>
  <div style="font-size:1.6rem;font-weight:800;color:#fff;letter-spacing:-.02em"><?= $fmt((int) $stats['revenue']) ?></div>
  <p class="admin-card__sub">Hors commandes annulées — <?= (int) $stats['customers'] ?> client(s) au total</p>
</div>

<?php if ($recentOrders !== []): ?>
<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Commandes récentes</h2>
    <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('orders') ?>">Tout voir</a>
  </div>
  <?php foreach ($recentOrders as $o): ?>
    <a class="admin-row" href="<?= Url::admin('orders/' . (int) $o['id']) ?>">
      <div class="admin-row__body">
        <div class="admin-row__title"><?= Security::e((string) $o['reference']) ?></div>
        <div class="admin-row__meta"><?= Security::e((string) $o['customer_name']) ?> · <?= Security::e(\App\Helpers\Format::date((string) $o['created_at'], 'd/m H:i')) ?></div>
      </div>
      <span class="<?= $badge((string) $o['status']) ?>"><?= Security::e(AdminOrderRepository::STATUS_LABELS[$o['status']] ?? $o['status']) ?></span>
      <div class="admin-row__end"><?= number_format((int) $o['total'], 0, ',', ' ') ?></div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($lowStock !== []): ?>
<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Stock faible</h2>
    <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('products') ?>?sort=stock">Gérer</a>
  </div>
  <?php foreach ($lowStock as $p): ?>
    <a class="admin-row" href="<?= Url::admin('products/' . (int) $p['id'] . '/edit') ?>">
      <div class="admin-row__body">
        <div class="admin-row__title"><?= Security::e((string) $p['name']) ?></div>
      </div>
      <?php $s = (int) $p['stock']; ?>
      <span class="admin-badge <?= $s === 0 ? 'admin-badge--out' : 'admin-badge--low' ?>"><?= $s === 0 ? 'Rupture' : $s . ' restant' . ($s > 1 ? 's' : '') ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($topProducts !== []): ?>
<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Meilleures ventes</h2>
  </div>
  <?php foreach ($topProducts as $i => $t): ?>
    <div class="admin-row" style="cursor:default">
      <div class="admin-row__end" style="width:1.5rem;color:#6b7280"><?= $i + 1 ?></div>
      <div class="admin-row__body">
        <div class="admin-row__title"><?= Security::e((string) $t['product_name']) ?></div>
        <div class="admin-row__meta"><?= (int) $t['qty'] ?> vendu(s)</div>
      </div>
      <div class="admin-row__end"><?= number_format((int) $t['revenue'], 0, ',', ' ') ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
