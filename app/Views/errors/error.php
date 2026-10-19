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
    419 => "Votre session a expirée. Rechargez la page et réessayez.",
    429 => "Trop de tentatives. Patientez quelques minutes puis réessayez.",
    500 => "Une erreur interne est survenue. Réessayez plus tard.",
];
$text = $message !== '' ? $message : ($default[$status] ?? 'Une erreur est survenue.');
?>
<div class="auth-wrap">
  <div class="auth-grid" aria-hidden="true"></div>

  <div class="auth-card auth-card--error text-center" style="max-width:430px">
    <div class="auth-code"><?= (int) $status ?></div>

    <h1 style="color:var(--mbw-white);font-size:1.3rem;margin:.6rem 0 .75rem"><?= Security::e($title) ?></h1>
    <p style="font-size:.95rem;line-height:1.6;color:var(--mbw-grey-400);margin:0 0 1.5rem">
      <?= Security::e($text) ?>
    </p>

    <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
      <a class="auth-submit" href="<?= Url::to('/') ?>" style="width:auto;padding:0 1.6rem;text-decoration:none;min-height:48px">
        <span class="auth-submit__label">Retour à la boutique</span>
      </a>
      <a href="javascript:history.back()"
         style="display:inline-flex;align-items:center;min-height:48px;padding:0 1.4rem;font-weight:700;font-size:.95rem;color:var(--mbw-grey-300);border:1px solid var(--mbw-grey-600);border-radius:13px;text-decoration:none;background:rgba(255,255,255,.04)">
        Retour
      </a>
    </div>
  </div>
</div>
