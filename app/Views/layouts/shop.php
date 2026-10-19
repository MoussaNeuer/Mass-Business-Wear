<?php
/**
 * Layout boutique publique : header sticky (logo, recherche, panier),
 * bottom nav mobile, footer, toasts.
 *
 * @var string $content
 * @var string $title
 */
use App\Helpers\Request;
use App\Helpers\Security;
use App\Helpers\Url;

$path = Request::path();
$isHome = $path === '/';
$isShop = $path === '/shop' || str_starts_with($path, '/category/');
$isCart = $path === '/cart';

$cartCountScript = <<<JS
(function(){try{var d=JSON.parse(localStorage.getItem('mbw_cart_v1')||'{"items":[]}');var n=(d.items||[]).reduce(function(a,i){return a+i.qty},0);document.querySelectorAll('.cart-badge').forEach(function(el){el.textContent=n>99?'99+':String(n);el.hidden=n===0})}catch(e){}})();
JS;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= Security::e($title ?? 'MASS BUSINESS WEAR') ?></title>
<meta name="description" content="<?= Security::e($meta ?? 'MASS BUSINESS WEAR — prêt-à-porter premium. Commandez en quelques clics sur WhatsApp.') ?>">
<meta name="theme-color" content="#0A0A0A">
<meta property="og:site_name" content="MASS BUSINESS WEAR">
<meta property="og:title" content="<?= Security::e($title ?? 'MASS BUSINESS WEAR') ?>">
<meta property="og:description" content="<?= Security::e($meta ?? 'Prêt-à-porter premium — commande en quelques clics sur WhatsApp.') ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_SN">
<meta property="og:image" content="<?= Security::e(\App\Helpers\BaseUrl::url('assets/img/icon-512.png')) ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= Security::e($title ?? 'MASS BUSINESS WEAR') ?>">
<meta name="twitter:description" content="<?= Security::e($meta ?? 'Prêt-à-porter premium — commande sur WhatsApp.') ?>">
<link rel="canonical" href="<?= Security::e(\App\Helpers\BaseUrl::url($path)) ?>">
<link rel="manifest" href="<?= Security::e(\App\Helpers\BaseUrl::url('manifest.json')) ?>">
<link rel="apple-touch-icon" href="<?= Url::asset('img/apple-touch-icon.png') ?>">
<link rel="icon" type="image/png" sizes="64x64" href="<?= Url::asset('img/favicon-64.png') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="MBW">
<?= Security::csrfMeta() ?>
<script>window.MBW_BASE = <?= json_encode(rtrim(\App\Helpers\BaseUrl::url('/'), '/') . '/', JSON_UNESCAPED_SLASHES) ?>;</script>
<link rel="stylesheet" href="<?= Url::asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/app.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/hero-cine.css') ?>">
</head>
<body>
<a class="sr-only" href="#main">Aller au contenu</a>

<header class="shop-header">
  <div class="container-app shop-header__inner">
    <a class="shop-header__brand" href="<?= Url::to('/') ?>">
      <img src="<?= Url::asset('img/logo.jpeg') ?>" alt="" width="34" height="34">
      <span>MASS <span class="text-red">WEAR</span></span>
    </a>

    <div class="shop-header__search">
      <form class="searchbar" role="search" method="get" action="<?= Url::to('shop') ?>" data-quick-search>
        <input type="search" name="q" value="<?= Security::e($_GET['q'] ?? '') ?>"
               placeholder="Rechercher un produit..." aria-label="Rechercher un produit"
               autocapitalize="off" autocorrect="off" enterkeyhint="search">
        <button type="submit" aria-label="Lancer la recherche">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </button>
        <div class="search-results" hidden></div>
      </form>
    </div>

    <div class="shop-header__icons">
      <a class="icon-btn" href="<?= Url::to('cart') ?>" aria-label="Panier">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 7h12l-1.2 12.2a1.5 1.5 0 0 1-1.5 1.3H8.7a1.5 1.5 0 0 1-1.5-1.3L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
        <span class="cart-badge" hidden>0</span>
      </a>
    </div>
  </div>
</header>

<main id="main" class="container-app page-with-nav">
<?= $content ?>
</main>

<footer class="shop-footer">
  <div class="container-app">
    <div class="row g-4">
      <div class="col-12 col-md-4">
        <h3>MASS <span class="text-red">BUSINESS WEAR</span></h3>
        <p>Prêt-à-porter premium. Commandez en ligne, recevez confirmation sur WhatsApp.</p>
      </div>
      <div class="col-6 col-md-3">
        <h3>Boutique</h3>
        <a href="<?= Url::to('shop') ?>">Tous les produits</a>
        <a href="<?= Url::to('category/vetements') ?>">Vêtements</a>
        <a href="<?= Url::to('category/ensembles') ?>">Ensembles</a>
        <a href="<?= Url::to('category/accessoires') ?>">Accessoires</a>
      </div>
      <div class="col-6 col-md-3">
        <h3>Aide</h3>
        <a href="<?= Url::to('contact') ?>">Nous contacter</a>
        <a href="<?= Url::to('cart') ?>">Mon panier</a>
      </div>
      <div class="col-12 col-md-2">
        <h3>Suivez-nous</h3>
        <a href="<?= Url::to('contact') ?>">WhatsApp</a>
        <a href="<?= Url::to('contact') ?>">Instagram</a>
      </div>
    </div>
    <p class="mt-4 mb-0" style="font-size:.8rem">© <?= date('Y') ?> MASS BUSINESS WEAR — Dakar, Sénégal</p>
  </div>
</footer>

<nav class="bottom-nav" aria-label="Navigation principale">
  <a href="<?= Url::to('/') ?>" class="<?= $isHome ? 'is-active' : '' ?>" <?= $isHome ? 'aria-current="page"' : '' ?>>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1V10Z"/></svg>
    Accueil
  </a>
  <a href="<?= Url::to('shop') ?>" class="<?= $isShop ? 'is-active' : '' ?>" <?= $isShop ? 'aria-current="page"' : '' ?>>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16l-1 13H5L4 7Z"/><path d="M8 7a4 4 0 0 1 8 0"/></svg>
    Boutique
  </a>
  <a href="<?= Url::to('cart') ?>" class="<?= $isCart ? 'is-active' : '' ?>" <?= $isCart ? 'aria-current="page"' : '' ?>>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 7h12l-1.2 12.2a1.5 1.5 0 0 1-1.5 1.3H8.7a1.5 1.5 0 0 1-1.5-1.3L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
    Panier
    <span class="cart-badge" hidden>0</span>
  </a>
  <a href="<?= Url::to('contact') ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16v12H8l-4 3V5Z"/></svg>
    Aide
  </a>
</nav>

<script src="<?= Url::asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= Url::asset('js/ui.js') ?>" defer></script>
<script src="<?= Url::asset('js/cart.js') ?>" defer></script>
<script src="<?= Url::asset('js/products.js') ?>" defer></script>
<script src="<?= Url::asset('js/hero-cine.js') ?>" defer></script>
<script><?= $cartCountScript ?></script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register(<?= json_encode(\App\Helpers\BaseUrl::url('service-worker.js'), JSON_UNESCAPED_SLASHES) ?>)
      .catch(function (e) { console.warn('SW shop', e); });
  });
}
</script>
</body>
</html>
