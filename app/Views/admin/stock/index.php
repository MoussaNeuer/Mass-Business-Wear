<?php
/**
 * Page Stock (Sprint D) : variantes triées par stock, alertes, mouvements.
 * @var array $variants
 * @var array $movements
 * @var array $filters
 * @var int $lowCount
 */
use App\Helpers\Security;
use App\Helpers\Url;
use App\Services\ImageService;

$low = !empty($filters['low']);
$q = (string) ($filters['q'] ?? '');
?>
<div class="admin-pagehead">
  <div>
    <h1>Stock</h1>
    <p>
      <?= count($variants) ?> variante(s)
      <?php if ($lowCount > 0): ?>
        · <span style="color:#f87171;font-weight:700"><?= $lowCount ?> en alerte</span>
      <?php endif; ?>
    </p>
  </div>
  <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('products') ?>">← Produits</a>
</div>

<form method="get" action="<?= Url::admin('stock') ?>" class="admin-searchbar">
  <input type="search" name="q" value="<?= Security::e($q) ?>" placeholder="Produit, taille, couleur, SKU…" data-admin-search>
  <?php if ($low): ?><input type="hidden" name="low" value="1"><?php endif; ?>
  <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">OK</button>
</form>

<div class="admin-chips">
  <a class="admin-chip <?= !$low ? 'is-active' : '' ?>" href="<?= Url::admin('stock' . ($q !== '' ? '?q=' . rawurlencode($q) : '')) ?>">Tous</a>
  <a class="admin-chip <?= $low ? 'is-active' : '' ?>" href="<?= Url::admin('stock?low=1' . ($q !== '' ? '&q=' . rawurlencode($q) : '')) ?>">⚠ Stock faible</a>
</div>

<?php if ($variants === []): ?>
  <div class="admin-card admin-empty">
    <div class="admin-empty__icon">📦</div>
    <h3>Aucune variante</h3>
    <p>Ajoutez des tailles / couleurs depuis un produit pour suivre le stock.</p>
  </div>
<?php else: ?>
  <?php foreach ($variants as $v): $stock = (int) $v['stock']; ?>
    <a class="admin-card admin-card--link" href="<?= Url::admin('products/' . (int) $v['product_id'] . '/edit') ?>" style="display:flex;gap:.75rem;align-items:center">
      <?php if (!empty($v['image_file'])): ?>
        <img class="admin-row__thumb" src="<?= Security::e(ImageService::thumbUrl((string) $v['image_file'])) ?>" alt="" width="44" height="55" loading="lazy">
      <?php else: ?>
        <div class="admin-row__thumb" style="display:grid;place-items:center;color:#4b5563;font-weight:800"><?= Security::e(mb_substr((string) $v['product_name'], 0, 1)) ?></div>
      <?php endif; ?>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;color:#fff;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= Security::e((string) $v['product_name']) ?>
        </div>
        <div class="admin-row__meta">
          <?php $label = trim(($v['size'] ?? '') . ' ' . ($v['color'] ?? '')); ?>
          <?= Security::e($label !== '' ? $label : 'Sans libellé') ?>
          <?php if (!empty($v['sku'])): ?> · <?= Security::e((string) $v['sku']) ?><?php endif; ?>
        </div>
      </div>
      <div style="text-align:right;flex:none">
        <?php if ($stock === 0): ?>
          <span class="admin-badge admin-badge--out">Rupture</span>
        <?php elseif ($stock <= 5): ?>
          <span class="admin-badge admin-badge--low"><?= $stock ?> pcs</span>
        <?php else: ?>
          <span style="font-size:.85rem;color:#d1d5db;font-weight:700"><?= $stock ?> pcs</span>
        <?php endif; ?>
      </div>
    </a>
  <?php endforeach; ?>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Mouvements récents</h2>
  </div>
  <?php if ($movements === []): ?>
    <p class="admin-card__sub">Aucun mouvement enregistré.</p>
  <?php else: ?>
    <ul class="admin-timeline">
      <?php foreach ($movements as $m): ?>
        <li>
          <div style="font-weight:600;color:#fff;font-size:.84rem">
            <?= Security::e((string) $m['product_name']) ?>
            <?php $label = trim(($m['size'] ?? '') . ' ' . ($m['color'] ?? '')); if ($label !== ''): ?>
              <span style="color:#6b7280;font-weight:400">(<?= Security::e($label) ?>)</span>
            <?php endif; ?>
            <span style="color:<?= (int) $m['change_qty'] < 0 ? '#f87171' : '#4ade80' ?>;font-weight:800">
              <?= (int) $m['change_qty'] > 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?>
            </span>
          </div>
          <div style="font-size:.75rem;color:#9ca3af">
            Stock après : <?= (int) $m['stock_after'] ?> ·
            <?= Security::e((string) ($m['reason'] ?? '')) ?>
          </div>
          <div class="admin-timeline__time"><?= Security::e(\App\Helpers\Format::date((string) $m['created_at'], 'd/m/Y H:i')) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
