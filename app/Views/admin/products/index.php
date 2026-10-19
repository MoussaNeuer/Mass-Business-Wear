<?php
/**
 * Liste des produits admin.
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
    $q = array_merge(['q' => $f['q'] ?? '', 'status' => $f['status'] ?? '', 'sort' => $f['sort'] ?? '', 'page' => 1], $over);
    $q = array_filter($q, static fn ($v) => $v !== '' && $v !== null && $v !== 1);
    return $q === [] ? '' : '?' . http_build_query($q);
};
$statusBadge = static fn (string $s): string => 'admin-badge admin-badge--' . $s;
?>
<div class="admin-pagehead">
  <div>
    <h1>Produits</h1>
    <p><?= (int) $total ?> produit(s) au total</p>
  </div>
  <div style="display:flex;gap:.4rem;flex-wrap:wrap">
    <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('categories') ?>">Catég.</a>
    <a class="admin-btn admin-btn--primary admin-btn--sm" href="<?= Url::admin('products/create') ?>">+ Nouveau</a>
  </div>
</div>

<form method="get" action="<?= Url::admin('products') ?>" class="admin-searchbar">
  <input type="search" name="q" value="<?= Security::e($f['q'] ?? '') ?>" placeholder="Rechercher un produit…" data-admin-search>
  <?php if (!empty($f['status'])): ?><input type="hidden" name="status" value="<?= Security::e($f['status']) ?>"><?php endif; ?>
  <?php if (!empty($f['sort'])): ?><input type="hidden" name="sort" value="<?= Security::e($f['sort']) ?>"><?php endif; ?>
  <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">OK</button>
</form>

<div class="admin-chips">
  <?php
  $chips = [
    ['', 'Tous'],
    ['active', 'Actifs'],
    ['draft', 'Brouillons'],
    ['archived', 'Archivés'],
  ];
  foreach ($chips as [$val, $label]):
    $on = ($f['status'] ?? '') === $val;
  ?>
    <a class="admin-chip <?= $on ? 'is-active' : '' ?>" href="<?= Url::admin('products' . $qs(['status' => $val, 'page' => 1])) ?>"><?= $label ?></a>
  <?php endforeach; ?>
  <a class="admin-chip <?= ($f['sort'] ?? '') === 'stock' ? 'is-active' : '' ?>" href="<?= Url::admin('products' . $qs(['sort' => 'stock', 'page' => 1])) ?>">Stock ↓</a>
</div>

<?php if ($items === []): ?>
  <div class="admin-card admin-empty">
    <div class="admin-empty__icon">📦</div>
    <h3>Aucun produit</h3>
    <p>Commencez par ajouter votre premier article.</p>
    <a class="admin-btn admin-btn--primary" href="<?= Url::admin('products/create') ?>">Créer un produit</a>
  </div>
<?php else: ?>
  <?php foreach ($items as $p): ?>
    <?php $stock = (int) $p['stock']; ?>
    <a class="admin-card admin-card--link" href="<?= Url::admin('products/' . (int) $p['id'] . '/edit') ?>" style="display:flex;gap:.75rem;align-items:center">
      <?php if (!empty($p['image'])): ?>
        <img class="admin-row__thumb" src="<?= Url::upload('products/' . Security::e($p['image'])) ?>" alt="" width="44" height="55" loading="lazy">
      <?php else: ?>
        <div class="admin-row__thumb" style="display:grid;place-items:center;color:#4b5563;font-weight:800"><?= Security::e(mb_substr($p['name'], 0, 1)) ?></div>
      <?php endif; ?>
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap">
          <span style="font-weight:700;color:#fff;font-size:.9rem"><?= Security::e((string) $p['name']) ?></span>
          <?php if ((int) $p['is_featured'] === 1): ?><span class="admin-badge admin-badge--featured">★</span><?php endif; ?>
        </div>
        <div class="admin-row__meta">
          <?= Security::e((string) ($p['category_name'] ?? '—')) ?> ·
          <?= number_format((int) $p['price'], 0, ',', ' ') ?> FCFA
          <?php if ($p['promo_price'] !== null): ?> <s style="color:#6b7280"><?= number_format((int) $p['promo_price'], 0, ',', ' ') ?></s><?php endif; ?>
        </div>
      </div>
      <div style="text-align:right;flex:none">
        <span class="<?= $statusBadge((string) $p['status']) ?>"><?= Security::e(ucfirst($p['status'])) ?></span>
        <div style="margin-top:.3rem">
          <?php if ($stock === 0): ?>
            <span class="admin-badge admin-badge--out">Rupture</span>
          <?php elseif ($stock <= 5): ?>
            <span class="admin-badge admin-badge--low"><?= $stock ?> pcs</span>
          <?php else: ?>
            <span style="font-size:.75rem;color:#6b7280"><?= $stock ?> pcs</span>
          <?php endif; ?>
        </div>
      </div>
    </a>
  <?php endforeach; ?>

  <?php if ($pages > 1): ?>
    <div class="admin-pagination">
      <?php if ($page > 1): ?><a href="<?= Url::admin('products' . $qs(['page' => $page - 1])) ?>">← Préc.</a><?php endif; ?>
      <span>Page <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= Url::admin('products' . $qs(['page' => $page + 1])) ?>">Suiv. →</a><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
