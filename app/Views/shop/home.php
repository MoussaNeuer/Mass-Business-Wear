<?php
/**
 * Accueil : hero cinématique (looks), catégories, nouveautés, best-sellers, promos, étapes.
 *
 * @var array $categories
 * @var array $new
 * @var array $best
 * @var array $promo
 * @var array $looks   n,webp,jpg,title,tag
 * @var string $whatsapp
 * @var string $shopName
 * @var string $currency
 */
use App\Helpers\Security;
use App\Helpers\Url;

$currency = $currency ?? 'FCFA';
$waLink = $whatsapp !== '' ? 'https://wa.me/221' . $whatsapp : null;
?>
<?php if ($looks !== []): ?>
<section class="hero-cine" data-hero-cine aria-roledescription="carrousel" aria-label="Looks de la collection">
  <div class="hero-cine__stage">
    <?php foreach ($looks as $i => $look): ?>
      <figure class="hero-cine__slide<?= $i === 0 ? ' is-active' : '' ?>" data-slide="<?= $i ?>"
              data-title="<?= Security::e($look['title']) ?>" data-tag="<?= Security::e($look['tag']) ?>"
              <?= $i === 0 ? '' : 'aria-hidden="true"' ?>>
        <picture>
          <?php if ($look['webp'] !== null): ?>
            <source srcset="<?= Security::e($look['webp']) ?>" type="image/webp">
          <?php endif; ?>
          <img
            src="<?= Security::e($look['jpg'] ?? $look['webp'] ?? '') ?>"
            alt="<?= Security::e($look['title']) ?> — collection MASS BUSINESS WEAR"
            width="900" height="1125"
            <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"' ?>
            draggable="false"
          >
        </picture>
        <div class="hero-cine__veil" aria-hidden="true"></div>
      </figure>
    <?php endforeach; ?>

    <div class="hero-cine__grain" aria-hidden="true"></div>
  </div>

  <div class="hero-cine__copy">
    <p class="hero-cine__kicker" data-hero-kicker>Collection <span>MBW</span> · <?= date('Y') ?></p>
    <h1 class="hero-cine__title" data-hero-title>
      <?php echo Security::e($looks[0]['title'] ?? 'Le style qui impressionne.'); ?>
    </h1>
    <p class="hero-cine__tag" data-hero-tag>
      <?php echo Security::e($looks[0]['tag'] ?? 'Prêt-à-porter premium. Commandez en quelques clics.'); ?>
    </p>
    <div class="hero-cine__actions" data-hero-cta>
      <a class="btn-mbw btn-primary hero-cine__btn" href="<?= Url::to('shop') ?>">Voir la collection</a>
      <?php if ($waLink !== null): ?>
        <a class="btn-mbw btn-dark hero-cine__btn" href="<?= Security::e($waLink) ?>" target="_blank" rel="noopener">WhatsApp</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="hero-cine__ui">
    <div class="hero-cine__progress" role="tablist" aria-label="Choisir un look">
      <?php foreach ($looks as $i => $look): ?>
        <button type="button" class="hero-cine__dot<?= $i === 0 ? ' is-active' : '' ?>"
                role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                aria-label="Look <?= $i + 1 ?> — <?= Security::e($look['title']) ?>"
                data-dot="<?= $i ?>"></button>
      <?php endforeach; ?>
    </div>
    <div class="hero-cine__counter" aria-live="polite"><span data-hero-index>1</span><i>/</i><?= count($looks) ?></div>
  </div>
</section>
<?php else: ?>
<section class="hero">
  <h1>Le style qui<br><span>impressionne.</span></h1>
  <p>Vêtements et accessoires premium pour hommes et femmes. Choisissez, commandez, et recevez votre confirmation sur WhatsApp en quelques minutes.</p>
  <div class="hero__actions">
    <a class="btn-mbw btn-primary" href="<?= Url::to('shop') ?>">Découvrir la boutique</a>
    <?php if ($waLink !== null): ?>
      <a class="btn-mbw btn-dark" href="<?= Security::e($waLink) ?>" target="_blank" rel="noopener">Écrire sur WhatsApp</a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($categories !== []): ?>
<section class="section">
  <div class="section-head">
    <h2>Catégories</h2>
    <a href="<?= Url::to('shop') ?>">Tout voir</a>
  </div>
  <div class="grid-categories">
    <?php foreach ($categories as $c): ?>
      <a class="category-card" href="<?= Url::to('category/' . $c['slug']) ?>">
        <strong><?= Security::e($c['name']) ?></strong>
        <span><?= (int) $c['products_count'] ?> produit<?= (int) $c['products_count'] > 1 ? 's' : '' ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($new !== []): ?>
<section class="section">
  <div class="section-head">
    <h2>Nouveautés</h2>
    <a href="<?= Url::to('shop?sort=recent') ?>">Voir tout</a>
  </div>
  <div class="rail">
    <?php foreach ($new as $p): ?>
      <?= \App\Helpers\View::partial('partials/product-card', ['p' => $p, 'currency' => $currency]) ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($best !== []): ?>
<section class="section">
  <div class="section-head">
    <h2>Les plus demandés</h2>
    <a href="<?= Url::to('shop?sort=popular') ?>">Voir tout</a>
  </div>
  <div class="rail">
    <?php foreach ($best as $p): ?>
      <?= \App\Helpers\View::partial('partials/product-card', ['p' => $p, 'currency' => $currency]) ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($promo !== []): ?>
<section class="section">
  <div class="section-head">
    <h2 class="text-red">Promotions</h2>
    <a href="<?= Url::to('shop?sort=price_asc') ?>">Voir tout</a>
  </div>
  <div class="grid-products">
    <?php foreach (array_slice($promo, 0, 4) as $p): ?>
      <?= \App\Helpers\View::partial('partials/product-card', ['p' => $p, 'currency' => $currency]) ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="section-head"><h2>Comment commander</h2></div>
  <div class="steps">
    <div class="step">
      <b>1</b>
      <strong>Choisissez vos articles</strong>
      <span>Taille, couleur, quantité — ajoutez au panier en un geste.</span>
    </div>
    <div class="step">
      <b>2</b>
      <strong>Envoyez votre commande</strong>
      <span>Nom, téléphone, zone de livraison. C'est tout.</span>
    </div>
    <div class="step">
      <b>3</b>
      <strong>Recevez confirmation WhatsApp</strong>
      <span>Nous validons avec vous puis préparons votre colis.</span>
    </div>
  </div>
</section>

<?php if ($new === [] && $best === [] && $promo === []): ?>
<section class="section">
  <div class="empty-state">
    <div class="icon" aria-hidden="true">🛍️</div>
    <h3>Le catalogue arrive</h3>
    <p>Les produits seront bientôt disponibles. Revenez très vite.</p>
    <a class="btn-mbw btn-primary" href="<?= Url::to('shop') ?>">Voir la boutique</a>
  </div>
</section>
<?php endif; ?>
