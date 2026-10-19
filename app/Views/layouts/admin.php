<?php
/**
 * Layout admin mobile-first (Phase 5) :
 * topbar sticky, bottom nav 5 onglets, flash toasts, animations fluides.
 * @var string $content
 * @var string $title
 * @var string $adminLogin
 * @var string $currentPath
 * @var list<array{m:string,t:string}> $flash
 */
use App\Helpers\Security;
use App\Helpers\Url;

$path = $currentPath ?? '';
$nav = [
    ['url' => Url::admin(), 'label' => 'Accueil', 'icon' => 'M3 12l9-8 9 8v8a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-8z', 'match' => '/admin' === rtrim($path, '/') || $path === '/admin/' || $path === '/admin'],
    ['url' => Url::admin('orders'), 'label' => 'Commandes', 'icon' => 'M6 7h12l-1 13H7L6 7zm3-3a3 3 0 0 1 6 0', 'match' => str_starts_with($path, '/admin/orders')],
    ['url' => Url::admin('products'), 'label' => 'Produits', 'icon' => 'M4 7h16v13H4V7zm4-3h8l1 3H7l1-3z', 'match' => str_starts_with($path, '/admin/products') || str_starts_with($path, '/admin/categories')],
    ['url' => Url::admin('customers'), 'label' => 'Clients', 'icon' => 'M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm-8 1a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm0 2c-2.7 0-8 1.3-8 4v3h10v-3c0-1.2.5-2.2 1.3-3A13 13 0 0 0 8 14zm8 0c-2.7 0-8 1.3-8 4v3h16v-3c0-2.7-5.3-4-8-4z', 'match' => str_starts_with($path, '/admin/customers') || str_starts_with($path, '/admin/stats')],
    ['url' => Url::admin('settings'), 'label' => 'Réglages', 'icon' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm9 4l-2 1.5.5 2.5-2.5.8-1.2 2.2-2.4-.5L12 22l-1.4-2.5-2.4.5-1.2-2.2-2.5-.8.5-2.5L3 12l2-1.5L4.5 8 7 7.2 8.2 5l2.4.5L12 3l1.4 2.5 2.4-.5L17 7.2 19.5 8 19 10.5 21 12z', 'match' => str_starts_with($path, '/admin/settings') || str_starts_with($path, '/admin/logs')],
];
$active = static function (array $item) use ($path): bool {
    if ($item['match']) {
        return true;
    }
    return false;
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= Security::e($title ?? 'Admin') ?> — MBW Admin</title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0A0A0A">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="MBW Admin">
<link rel="manifest" href="<?= \App\Helpers\BaseUrl::url('manifest.admin.json') ?>">
<link rel="apple-touch-icon" href="<?= Url::asset('img/apple-touch-icon.png') ?>">
<link rel="icon" type="image/png" sizes="64x64" href="<?= Url::asset('img/favicon-64.png') ?>">
<?= Security::csrfMeta() ?>
<link rel="stylesheet" href="<?= Url::asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/app.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/admin.css') ?>">
</head>
<body class="theme-dark admin-body">

<a class="sr-only" href="#admin-main">Aller au contenu</a>

<header class="admin-topbar">
  <div class="admin-topbar__inner">
    <a class="admin-topbar__brand" href="<?= Url::admin() ?>">
      <img src="<?= Url::asset('img/logo.jpeg') ?>" alt="" width="30" height="30">
      <span>MBW <em>Admin</em></span>
    </a>
    <div class="admin-topbar__actions">
      <button type="button" id="admin-install-btn" class="admin-install-btn" hidden>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
        <span class="admin-install__label">Installer</span>
      </button>
      <a class="admin-icon-btn" href="<?= Url::to('/') ?>" target="_blank" rel="noopener" aria-label="Voir la boutique">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 5h5v5M19 5l-7 7M10 5H5v14h14v-5"/></svg>
      </a>
      <form method="post" action="<?= Url::admin('logout') ?>" style="margin:0">
        <?= Security::csrfField() ?>
        <button class="admin-icon-btn" type="submit" aria-label="Déconnexion">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        </button>
      </form>
    </div>
  </div>
</header>

<main id="admin-main" class="admin-main">
  <?= $content ?>
</main>

<nav class="admin-bottomnav" aria-label="Navigation admin">
  <?php foreach ($nav as $item): ?>
    <?php $on = $active($item); ?>
    <a href="<?= $item['url'] ?>" class="<?= $on ? 'is-active' : '' ?>" <?= $on ? 'aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="<?= $item['icon'] ?>"/></svg>
      <span><?= Security::e($item['label']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if (!empty($flash)): ?>
<div class="admin-flash-stack" id="admin-flash" role="status" aria-live="polite">
  <?php foreach ($flash as $f): ?>
    <div class="admin-flash admin-flash--<?= Security::e($f['t']) ?>">
      <?= Security::e($f['m']) ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script src="<?= Url::asset('js/admin.js') ?>" defer></script>
<script src="<?= Url::asset('js/admin-pwa.js') ?>" defer></script>
</body>
</html>
