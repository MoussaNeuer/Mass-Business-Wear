<?php
/**
 * Layout minimal pour les pages hors-ligne (login, erreurs).
 * @var string $content
 * @var string $title
 */
use App\Helpers\Security;
use App\Helpers\Url;
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
<link rel="stylesheet" href="<?= Url::asset('css/tokens.css') ?>">
<link rel="stylesheet" href="<?= Url::asset('css/app.css') ?>">
</head>
<body class="theme-dark">
<?= $content ?>
</body>
</html>
