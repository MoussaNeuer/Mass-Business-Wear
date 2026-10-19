<?php
/**
 * Fiche produit — Phase 3 : galerie swipeable, variantes croisées,
 * quantité, CTA sticky "Ajouter au panier" (localStorage).
 *
 * @var array $product
 * @var array $images   file,sort_order
 * @var array $variants id,size,color,price_override,stock
 * @var array $sizes
 * @var array $colors
 * @var array $similar
 * @var ?string $badge  out|promo|new|null
 * @var string $currency
 * @var string $shareUrl
 * @var string $whatsapp
 */
use App\Helpers\Security;
use App\Helpers\Url;

$eff = (int) $product['eff_price'];
$old = $product['promo_price'] !== null ? (int) $product['price'] : null;
$stock = (int) $product['stock'];
$hasSize = $sizes !== [];
$hasColor = $colors !== [];

$gallery = [];
foreach ($images as $img) {
    $gallery[] = Url::upload('products/' . $img['file']);
}
$mainImage = $gallery[0] ?? null;

// Variantes pour le JS : prix effectif + stock
$variantsForJs = array_map(static function (array $v) use ($eff): array {
    return [
        'id' => (int) $v['id'],
        'size' => $v['size'],
        'color' => $v['color'],
        'price_override' => $v['price_override'] !== null ? (int) $v['price_override'] : null,
        'stock' => (int) $v['stock'],
    ];
}, $variants);

$pdpData = [
    'id' => (int) $product['id'],
    'name' => (string) $product['name'],
    'slug' => (string) $product['slug'],
    'price' => $eff,
    'oldPrice' => $old,
    'currency' => $currency,
    'image' => $mainImage,
    'stock' => $stock,
    'hasSize' => $hasSize,
    'hasColor' => $hasColor,
    'variants' => $variantsForJs,
    'whatsapp' => (string) $whatsapp,
    'shareUrl' => $shareUrl,
];
?>
<article class="pdp" data-pdp>
  <div class="pdp__gallery">
    <?php if ($badge === 'promo'): ?><span class="badge-mbw badge-promo" style="position:absolute;top:.75rem;left:.75rem;z-index:2">Promo</span><?php endif; ?>
    <?php if ($badge === 'out'): ?><span class="badge-mbw badge-out" style="position:absolute;top:.75rem;left:.75rem;z-index:2">Rupture</span><?php endif; ?>
    <?php if ($badge === 'new'): ?><span class="badge-mbw badge-new" style="position:absolute;top:.75rem;left:.75rem;z-index:2">Nouveau</span><?php endif; ?>
    <?php if ($gallery !== []): ?>
      <div class="pdp__strip" data-gallery-strip>
        <?php foreach ($gallery as $i => $src): ?>
          <img class="pdp__slide" src="<?= Security::e($src) ?>" alt="<?= Security::e($product['name']) ?> — vue <?= $i + 1 ?>" width="800" height="1000" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="product-card__placeholder" style="aspect-ratio:4/5;position:relative"><?= Security::e(mb_substr($product['name'], 0, 1)) ?></div>
    <?php endif; ?>
  </div>

  <?php if (count($gallery) > 1): ?>
    <div class="pdp__dots" data-gallery-dots aria-hidden="true">
      <?php foreach ($gallery as $i => $img): ?>
        <span class="<?= $i === 0 ? 'is-active' : '' ?>"></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="pdp__info">
    <div class="pdp__meta">
      <?php if (!empty($product['category_name'])): ?>
        <a class="badge-mbw badge-cat" href="<?= Url::to('category/' . $product['category_slug']) ?>"><?= Security::e($product['category_name']) ?></a>
      <?php endif; ?>
    </div>

    <h1><?= Security::e($product['name']) ?></h1>

    <div class="product-card__price mb-2">
      <span class="price price--lg<?= $old === null ? ' price--plain' : '' ?>" data-pdp-price>
        <?= number_format($eff, 0, ',', ' ') ?> <?= Security::e($currency) ?>
        <?php if ($old !== null): ?><s class="price--old"><?= number_format($old, 0, ',', ' ') ?> <?= Security::e($currency) ?></s><?php endif; ?>
      </span>
    </div>
    <p class="text-muted" style="font-size:.85rem;margin:0 0 .75rem" data-pdp-stock></p>

    <?php if ($hasSize): ?>
      <div class="pdp__variants">
        <h3>Taille <span class="text-muted" style="font-weight:400;font-size:.8rem">(obligatoire)</span></h3>
        <div class="pdp__pills" data-variants="size" role="group" aria-label="Choisir une taille">
          <?php foreach ($sizes as $size): ?>
            <button type="button" class="variant-pill" data-value="<?= Security::e($size) ?>" aria-pressed="false">
              <?= Security::e($size) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($hasColor): ?>
      <div class="pdp__variants">
        <h3>Couleur <span class="text-muted" style="font-weight:400;font-size:.8rem">(obligatoire)</span></h3>
        <div class="pdp__pills" data-variants="color" role="group" aria-label="Choisir une couleur">
          <?php foreach ($colors as $color): ?>
            <button type="button" class="variant-pill" data-value="<?= Security::e($color) ?>" aria-pressed="false">
              <?= Security::e($color) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="pdp__variants">
      <h3>Quantité</h3>
      <div class="qty-stepper" data-qty-wrap>
        <button type="button" data-qty-act="dec" aria-label="Diminuer la quantité">−</button>
        <input type="number" data-qty value="1" min="1" max="20" inputmode="numeric" aria-label="Quantité">
        <button type="button" data-qty-act="inc" aria-label="Augmenter la quantité">+</button>
      </div>
    </div>

    <?php if (!empty($product['description'])): ?>
      <div class="pdp__desc mt-3"><?= nl2br(Security::e($product['description'])) ?></div>
    <?php endif; ?>

    <div class="mt-4" style="display:flex;gap:.75rem;flex-wrap:wrap">
      <button type="button" class="btn-mbw btn-dark btn-sm" data-share>Partager</button>
      <button type="button" class="btn-mbw btn-dark btn-sm" data-copy-link>Copier le lien</button>
      <a class="btn-mbw btn-ghost btn-sm" data-wa-order href="https://wa.me/<?= $whatsapp !== '' ? '221' . Security::e($whatsapp) : '' ?>" target="_blank" rel="noopener">Commander sur WhatsApp</a>
    </div>
  </div>

  <div class="sticky-cta">
    <div class="sticky-cta__price" data-pdp-price><?= number_format($eff, 0, ',', ' ') ?> <?= Security::e($currency) ?></div>
    <button type="button" class="btn-mbw btn-primary sticky-cta__btn" data-add-to-cart <?= $stock <= 0 ? 'disabled' : '' ?>>
      <span data-cta-label>Ajouter au panier</span>
    </button>
  </div>
</article>

<?php if ($similar !== []): ?>
<section class="section">
  <div class="section-head"><h2>Vous aimerez aussi</h2></div>
  <div class="rail">
    <?php foreach ($similar as $p): ?>
      <?= \App\Helpers\View::partial('partials/product-card', ['p' => $p, 'currency' => $currency]) ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($jsonLd)): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<script type="application/json" id="pdp-data"><?= json_encode($pdpData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= Url::asset('js/pdp.js') ?>" defer></script>
