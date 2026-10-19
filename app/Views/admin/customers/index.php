<?php
/**
 * Liste des clients (agrégée depuis les commandes).
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var array $filters
 */
use App\Helpers\Security;
use App\Helpers\Url;

$f = $filters ?? [];
$qs = static function (array $over) use ($f): string {
    $q = array_merge(['q' => $f['q'] ?? '', 'page' => 1], $over);
    $q = array_filter($q, static fn ($v) => $v !== '');
    return $q === [] ? '' : '?' . http_build_query($q);
};
?>
<div class="admin-pagehead">
  <div>
    <h1>Clients</h1>
    <p><?= (int) $total ?> client(s)</p>
  </div>
  <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('stats') ?>">Stats →</a>
</div>

<form method="get" action="<?= Url::admin('customers') ?>" class="admin-searchbar">
  <input type="search" name="q" value="<?= Security::e($f['q'] ?? '') ?>" placeholder="Nom ou téléphone…" data-admin-search>
  <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">OK</button>
</form>

<?php if ($items === []): ?>
  <div class="admin-card admin-empty">
    <div class="admin-empty__icon">👥</div>
    <h3>Aucun client</h3>
    <p>Les clients apparaissent dès la première commande.</p>
  </div>
<?php else: ?>
  <?php foreach ($items as $c): ?>
    <a class="admin-card admin-card--link" href="<?= Url::admin('customers/' . (int) $c['id']) ?>">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
        <div style="min-width:0">
          <div style="font-weight:700;color:#fff;font-size:.9rem"><?= Security::e((string) $c['name']) ?></div>
          <div class="admin-row__meta"><?= Security::e((string) $c['phone']) ?></div>
          <?php if (!empty($c['last_order_at'])): ?>
            <div class="admin-row__meta">Dernière : <?= Security::e(\App\Helpers\Format::date((string) $c['last_order_at'], 'd/m/Y')) ?></div>
          <?php endif; ?>
        </div>
        <div style="text-align:right;flex:none">
          <div style="font-weight:800;color:#fff"><?= number_format((int) $c['total_spent'], 0, ',', ' ') ?></div>
          <div class="admin-row__meta"><?= (int) $c['orders_count'] ?> cmd</div>
        </div>
      </div>
    </a>
  <?php endforeach; ?>

  <?php if ($pages > 1): ?>
    <div class="admin-pagination">
      <?php if ($page > 1): ?><a href="<?= Url::admin('customers' . $qs(['page' => $page - 1])) ?>">← Préc.</a><?php endif; ?>
      <span>Page <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= Url::admin('customers' . $qs(['page' => $page + 1])) ?>">Suiv. →</a><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
