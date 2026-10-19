<?php
/**
 * Statistiques avancées — période 7/30/90 jours.
 * @var int $days
 * @var array $summary
 * @var array $vs
 * @var array $byDay
 * @var array $byZone
 * @var array $topProducts
 * @var array $topCustomers
 * @var array $statusBreakdown
 * @var array $repeat
 * @var array $monthly
 */
use App\Helpers\Security;
use App\Helpers\Url;

$fmtPct = static function (?float $p): string {
    if ($p === null) {
        return 'n/a';
    }
    return ($p > 0 ? '+' : '') . number_format($p, 1, ',', ' ') . ' %';
};
$pctClass = static fn (?float $p): string => $p === null ? '' : ($p > 0 ? 'is-up' : ($p < 0 ? 'is-down' : 'is-flat'));
$statusLabels = [
    'NEW' => 'Nouvelle', 'CONFIRMED' => 'Confirmée', 'PROCESSING' => 'En préparation',
    'READY' => 'Prête', 'DELIVERED' => 'Livrée', 'CANCELLED' => 'Annulée',
];
$maxZone = 1;
foreach ($byZone as $z) {
    $maxZone = max($maxZone, (int) $z['revenue']);
}
$maxDay = 1;
foreach ($byDay as $d) {
    $maxDay = max($maxDay, (int) $d['revenue']);
}
$maxMonth = 1;
foreach ($monthly as $m) {
    $maxMonth = max($maxMonth, (int) $m['revenue']);
}
$totalRepeat = (int) $repeat['repeat'] + (int) $repeat['one_time'];
$repeatPct = $totalRepeat > 0 ? round(((int) $repeat['repeat'] / $totalRepeat) * 100) : 0;
?>
<div class="admin-pagehead">
  <div>
    <h1>Statistiques</h1>
    <p>Période de <?= $days ?> jours</p>
  </div>
  <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin() ?>">← Accueil</a>
</div>

<div class="admin-chips">
  <?php foreach ([7 => '7 j', 30 => '30 j', 90 => '90 j'] as $d => $label): ?>
    <a class="admin-chip <?= $days === $d ? 'is-active' : '' ?>" href="<?= Url::admin('stats?days=' . $d) ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="admin-stats-row">
  <div class="admin-stat">
    <div class="admin-stat__label">Chiffre d'affaires</div>
    <div class="admin-stat__value admin-stat__value--sm"><?= number_format((int) $summary['revenue'], 0, ',', ' ') ?></div>
    <div class="admin-stat__delta <?= $pctClass($vs['revenue_delta_pct'] ?? null) ?>"><?= Security::e($fmtPct($vs['revenue_delta_pct'] ?? null)) ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat__label">Commandes</div>
    <div class="admin-stat__value"><?= (int) $summary['orders'] ?></div>
    <div class="admin-stat__delta <?= $pctClass($vs['orders_delta_pct'] ?? null) ?>"><?= Security::e($fmtPct($vs['orders_delta_pct'] ?? null)) ?></div>
  </div>
</div>

<div class="admin-stats-row">
  <div class="admin-stat">
    <div class="admin-stat__label">Panier moyen</div>
    <div class="admin-stat__value admin-stat__value--sm"><?= number_format((int) $summary['avg_order'], 0, ',', ' ') ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat__label">Articles vendus</div>
    <div class="admin-stat__value"><?= (int) $summary['items'] ?></div>
  </div>
  <div class="admin-stat">
    <div class="admin-stat__label">Clients actifs</div>
    <div class="admin-stat__value"><?= (int) $summary['customers'] ?></div>
    <div class="admin-stat__delta is-flat">+<?= (int) $summary['new_customers'] ?> nouveaux</div>
  </div>
</div>

<!-- Tendance CA par jour -->
<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">CA par jour</h2></div>
  <?php if ($byDay === []): ?>
    <p class="admin-card__sub">Aucune donnée sur la période.</p>
  <?php else: ?>
    <div class="admin-chart" role="img" aria-label="Histogramme du chiffre d'affaires par jour">
      <?php foreach ($byDay as $d): $h = max(4, (int) round(((int) $d['revenue'] / $maxDay) * 100)); ?>
        <div class="admin-chart__col" title="<?= Security::e((string) $d['day']) ?> : <?= number_format((int) $d['revenue'], 0, ',', ' ') ?> (<?= (int) $d['orders'] ?> cmd)">
          <div class="admin-chart__bar" style="height:<?= $h ?>%"></div>
          <span class="admin-chart__label"><?= Security::e(substr((string) $d['day'], 8, 2)) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Top produits -->
<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Top produits</h2></div>
  <?php if ($topProducts === []): ?>
    <p class="admin-card__sub">Aucune vente sur la période.</p>
  <?php else: ?>
    <?php foreach ($topProducts as $i => $p): ?>
      <div class="admin-row" style="cursor:default">
        <div class="admin-row__body">
          <div class="admin-row__title"><span class="admin-rank"><?= $i + 1 ?></span> <?= Security::e((string) $p['product_name']) ?></div>
          <div class="admin-row__meta"><?= (int) $p['qty'] ?> vendu(s)</div>
        </div>
        <div class="admin-row__end"><?= number_format((int) $p['revenue'], 0, ',', ' ') ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Top clients -->
<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Top clients</h2></div>
  <?php if ($topCustomers === []): ?>
    <p class="admin-card__sub">Aucune commande sur la période.</p>
  <?php else: ?>
    <?php foreach ($topCustomers as $i => $c): ?>
      <a class="admin-row" href="<?= Url::admin('customers/' . (int) $c['customer_id']) ?>">
        <div class="admin-row__body">
          <div class="admin-row__title"><span class="admin-rank"><?= $i + 1 ?></span> <?= Security::e((string) $c['name']) ?></div>
          <div class="admin-row__meta"><?= Security::e((string) $c['phone']) ?> · <?= (int) $c['orders'] ?> cmd</div>
        </div>
        <div class="admin-row__end"><?= number_format((int) $c['revenue'], 0, ',', ' ') ?></div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Zones -->
<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Zones de livraison</h2></div>
  <?php if ($byZone === []): ?>
    <p class="admin-card__sub">Aucune donnée.</p>
  <?php else: ?>
    <?php foreach ($byZone as $z): $w = max(4, (int) round(((int) $z['revenue'] / $maxZone) * 100)); ?>
      <div class="admin-bar-row">
        <div class="admin-bar-row__head">
          <span><?= Security::e((string) $z['zone']) ?></span>
          <span><?= (int) $z['orders'] ?> · <?= number_format((int) $z['revenue'], 0, ',', ' ') ?></span>
        </div>
        <div class="admin-bar-row__track"><div class="admin-bar-row__fill" style="width:<?= $w ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Répartition statuts + fidélité -->
<div class="admin-stats-row">
  <div class="admin-card" style="margin:0">
    <div class="admin-card__head"><h2 class="admin-card__title">Statuts</h2></div>
    <?php if ($statusBreakdown === []): ?>
      <p class="admin-card__sub">—</p>
    <?php else: ?>
      <?php foreach ($statusBreakdown as $s): ?>
        <div class="admin-row" style="cursor:default;padding:.4rem 0">
          <div class="admin-row__body">
            <span class="admin-badge admin-badge--<?= Security::e(strtolower((string) $s['status'])) ?>">
              <?= Security::e($statusLabels[$s['status']] ?? $s['status']) ?>
            </span>
          </div>
          <div class="admin-row__end"><?= (int) $s['n'] ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="admin-card" style="margin:0">
    <div class="admin-card__head"><h2 class="admin-card__title">Fidélité</h2></div>
    <div class="admin-stat" style="border:0;padding:0">
      <div class="admin-stat__label">Clients récurrents</div>
      <div class="admin-stat__value"><?= $repeatPct ?>%</div>
      <div class="admin-row__meta" style="margin-top:.25rem">
        <?= (int) $repeat['repeat'] ?> récurrent(s) · <?= (int) $repeat['one_time'] ?> unique(s)
      </div>
    </div>
  </div>
</div>

<!-- Mensuel -->
<div class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">6 derniers mois</h2></div>
  <?php if ($monthly === []): ?>
    <p class="admin-card__sub">Aucune donnée.</p>
  <?php else: ?>
    <div class="admin-chart admin-chart--months" role="img" aria-label="Histogramme mensuel">
      <?php foreach ($monthly as $m): $h = max(4, (int) round(((int) $m['revenue'] / $maxMonth) * 100)); ?>
        <div class="admin-chart__col" title="<?= Security::e((string) $m['month']) ?> : <?= number_format((int) $m['revenue'], 0, ',', ' ') ?>">
          <div class="admin-chart__bar" style="height:<?= $h ?>%"></div>
          <span class="admin-chart__label"><?= Security::e(substr((string) $m['month'], 5)) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
