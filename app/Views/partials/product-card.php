<?php
/**
 * Carte produit — utilisée par l'accueil, le catalogue et les rails.
 *
 * @var array $p  ligne produit (ProductRepository::SELECT_CARD)
 * @var string $currency
 */
use App\Helpers\Security;
use App\Helpers\Url;

$eff = (int) $p['eff_price'];
$old = $p['promo_price'] !== null ? (int) $p['price'] : null;
$stock = (int) $p['stock'];
$isNew = strtotime((string) $p['created_at']) > time() - 7 * 86400;
$image = $p['image'] !== null && $p['image'] !== '' ? Url::upload('products/' . $p['image']) : null;
?>
<article class="product-card">
  <a href="<?= Url::to('product/' . $p['slug']) ?>">
    <div class="product-card__media">
      <?php if ($old !== null): ?><span class="badge-mbw badge-promo">Promo</span><?php endif; ?>
      <?php if ($stock <= 0): ?><span class="badge-mbw badge-out">Rupture</span>
      <?php elseif ($isNew && $old === null): ?><span class="badge-mbw badge-new">Nouveau</span><?php endif; ?>
      <?php if ($image !== null): ?>
        <img src="<?= Security::e($image) ?>" alt="<?= Security::e($p['name']) ?>" loading="lazy" width="400" height="500">
      <?php else: ?>
        <div class="product-card__placeholder" aria-hidden="true"><?= Security::e(mb_substr($p['name'], 0, 1)) ?></div>
      <?php endif; ?>
    </div>
    <div class="product-card__body">
      <?php if (!empty($p['category_name'])): ?>
        <span class="product-card__cat"><?= Security::e((string) $p['category_name']) ?></span>
      <?php endif; ?>
      <span class="product-card__name"><?= Security::e($p['name']) ?></span>
      <div class="product-card__price">
        <span class="price<?= $old === null ? ' price--plain' : '' ?>"><?= number_format($eff, 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
        <?php if ($old !== null): ?><s class="price--old"><?= number_format($old, 0, ',', ' ') ?></s><?php endif; ?>
      </div>
    </div>
  </a>
</article>
