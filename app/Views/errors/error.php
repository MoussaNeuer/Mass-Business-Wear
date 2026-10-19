<?php
/**
 * Page d'erreur générique (404, 403, 419, 429, 500).
 * @var int $status
 * @var string $message
 */
use App\Helpers\Security;
use App\Helpers\Url;

$titles = [
    403 => 'Accès refusé',
    404 => 'Page introuvable',
    405 => 'Méthode non autorisée',
    419 => 'Session expirée',
    429 => 'Trop de requêtes',
    500 => 'Erreur serveur',
];
$title = $titles[$status] ?? 'Erreur';
$default = [
    403 => "Vous n'avez pas le droit d'accéder à cette page, ou votre session a expiré.",
    404 => "La page demandée n'existe pas ou a été déplacée.",
    405 => "Cette action n'est pas autorisée ici.",
    419 => "Votre session a expiré. Rechargez la page et réessayez.",
    429 => "Trop de tentatives. Patientez quelques minutes puis réessayez.",
    500 => "Une erreur interne est survenue. Réessayez plus tard.",
];
$text = $message !== '' ? $message : ($default[$status] ?? 'Une erreur est survenue.');
?>
<div class="auth-wrap">
  <div class="auth-card text-center" style="max-width:420px">
    <div style="font-size:4.5rem;font-weight:800;color:var(--mbw-red);line-height:1"><?= (int) $status ?></div>
    <h1 style="color:var(--mbw-white);font-size:1.25rem;margin:.5rem 0 .75rem"><?= Security::e($title) ?></h1>
    <p class="text-muted" style="font-size:.95rem"><?= Security::e($text) ?></p>
    <div class="mt-4" style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
      <a class="btn-mbw btn-primary" href="<?= Url::to('/') ?>">Accueil</a>
      <a class="btn-mbw btn-secondary" style="color:var(--mbw-grey-300);border-color:var(--mbw-grey-600)"
         href="javascript:history.back()">Retour</a>
    </div>
  </div>
</div>
