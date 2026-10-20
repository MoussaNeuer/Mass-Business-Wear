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

    <div class="mt-4 pdp__actions">
      <button type="button" class="btn-mbw btn-dark btn-sm" data-share>Partager</button>
      <button type="button" class="btn-mbw btn-dark btn-sm" data-copy-link>Copier le lien</button>
    </div>

    <a class="pdp__wa" data-wa-order
       href="https://wa.me/<?= $whatsapp !== '' ? '221' . Security::e($whatsapp) : '' ?>"
       target="_blank" rel="noopener">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.32 4.96L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24 2.2 0 4.27.86 5.83 2.42a8.19 8.19 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.25 8.23Zm4.52-6.17c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.16.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.65.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.1-.22-.16-.47-.29Z"/>
      </svg>
      Commander sur WhatsApp
    </a>
  </div>

  <div class="sticky-cta">
    <div class="sticky-cta__price" data-pdp-total><?= number_format($eff, 0, ',', ' ') ?> <?= Security::e($currency) ?></div>
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
