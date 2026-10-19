<?php
/**
 * Liste des commandes admin.
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var array $filters
 * @var array $statusCounts
 * @var array $statusLabels
 */
use App\Helpers\Security;
use App\Helpers\Url;

$f = $filters ?? [];
$badge = static fn (string $s): string => 'admin-badge admin-badge--' . strtolower($s);
$qs = static function (array $over) use ($f): string {
    $q = array_merge(['status' => $f['status'] ?? '', 'q' => $f['q'] ?? '', 'page' => 1], $over);
    $q = array_filter($q, static fn ($v) => $v !== '' && $v !== null);
    return $q === [] ? '' : '?' . http_build_query($q);
};
?>
<div class="admin-pagehead">
  <div>
    <h1>Commandes</h1>
    <p><?= (int) $total ?> commande(s)</p>
  </div>
</div>

<form method="get" action="<?= Url::admin('orders') ?>" class="admin-searchbar">
  <input type="search" name="q" value="<?= Security::e($f['q'] ?? '') ?>" placeholder="Référence, nom, téléphone…" data-admin-search>
  <?php if (!empty($f['status'])): ?><input type="hidden" name="status" value="<?= Security::e($f['status']) ?>"><?php endif; ?>
  <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">OK</button>
</form>

<div class="admin-chips">
  <a class="admin-chip <?= empty($f['status']) ? 'is-active' : '' ?>" href="<?= Url::admin('orders') ?>">
    Tous <span class="admin-chip__count"><?= array_sum($statusCounts) ?></span>
  </a>
  <?php foreach ($statusLabels as $val => $label): ?>
    <?php $n = (int) ($statusCounts[$val] ?? 0); if ($n === 0 && $val !== 'NEW') continue; ?>
    <a class="admin-chip <?= ($f['status'] ?? '') === $val ? 'is-active' : '' ?>" href="<?= Url::admin('orders' . $qs(['status' => $val])) ?>">
      <?= Security::e($label) ?> <span class="admin-chip__count"><?= $n ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($items === []): ?>
  <div class="admin-card admin-empty">
    <div class="admin-empty__icon">🧾</div>
    <h3>Aucune commande</h3>
    <p>Les commandes clients apparaîtront ici.</p>
  </div>
<?php else: ?>
  <?php foreach ($items as $o): ?>
    <a class="admin-card admin-card--link" href="<?= Url::admin('orders/' . (int) $o['id']) ?>">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
        <div style="min-width:0">
          <div style="font-weight:700;color:#fff;font-size:.9rem"><?= Security::e((string) $o['reference']) ?></div>
          <div class="admin-row__meta">
            <?= Security::e((string) $o['customer_name']) ?> · <?= Security::e((string) $o['customer_phone']) ?>
          </div>
          <div class="admin-row__meta">
            <?= Security::e(\App\Helpers\Format::date((string) $o['created_at'], 'd/m/Y H:i')) ?> ·
            <?= (int) $o['items_count'] ?> article(s)
          </div>
        </div>
        <div style="text-align:right;flex:none">
          <span class="<?= $badge((string) $o['status']) ?>"><?= Security::e($statusLabels[$o['status']] ?? $o['status']) ?></span>
          <div style="margin-top:.35rem;font-weight:800;color:#fff"><?= number_format((int) $o['total'], 0, ',', ' ') ?> FCFA</div>
        </div>
      </div>
    </a>
  <?php endforeach; ?>

  <?php if ($pages > 1): ?>
    <div class="admin-pagination">
      <?php if ($page > 1): ?><a href="<?= Url::admin('orders' . $qs(['page' => $page - 1])) ?>">← Préc.</a><?php endif; ?>
      <span>Page <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= Url::admin('orders' . $qs(['page' => $page + 1])) ?>">Suiv. →</a><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
