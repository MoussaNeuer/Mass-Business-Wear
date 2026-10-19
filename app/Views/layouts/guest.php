<?php
/**
 * Layout minimal pour les pages hors-ligne (login, erreurs).
 * @var string $content
 * @var string $title
 */
use App\Helpers\Security;
use App\Helpers\Url;

// Versionnement assets (Cache-Control 86400) : ?v=timestamp de dernière modif
$assetV = static function (string $path): string {
    $file = dirname(__DIR__, 3) . '/public/assets/' . $path;
    return is_file($file) ? '?v=' . (int) filemtime($file) : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= Security::e($title ?? 'MASS BUSINESS WEAR') ?></title>
<meta name="theme-color" content="#0A0A0A">
<meta name="robots" content="noindex">
<?= Security::csrfMeta() ?>
<link rel="stylesheet" href="<?= Url::asset('css/tokens.css') ?><?= $assetV('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/app.css') ?><?= $assetV('css/app.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/auth.css') ?><?= $assetV('css/auth.css') ?>">
</head>
<body class="theme-dark">
<?= $content ?>
</body>
</html>
