<?php
/**
 * Contact : WhatsApp, téléphone, réseaux, infos.
 *
 * @var string $whatsapp
 * @var string $phone
 * @var string $email
 * @var string $address
 * @var string $hours
 * @var string $instagram
 * @var string $facebook
 * @var string $tiktok
 */
use App\Helpers\Security;
use App\Helpers\Url;

$wa = preg_replace('/\D+/', '', $whatsapp);
?>
<section class="mt-4">
  <h1 style="font-size:1.5rem;font-weight:800">Nous contacter</h1>
  <p class="text-muted">La façon la plus rapide : WhatsApp. Réponse en général sous quelques minutes aux heures ouvrées.</p>

  <?php if ($wa !== ''): ?>
    <a class="btn-mbw btn-primary btn-block mt-3" href="https://wa.me/221<?= Security::e($wa) ?>" target="_blank" rel="noopener">
      Écrire sur WhatsApp
    </a>
  <?php endif; ?>

  <div class="card-mbw mt-4">
    <h3 style="font-size:1rem;margin:0 0 .75rem">Coordonnées</h3>
    <ul class="check-list">
      <?php if ($phone !== ''): ?>
        <li><span class="dot dot-ok"></span> Téléphone : <a href="tel:<?= Security::e($phone) ?>" style="color:var(--mbw-red)"><?= Security::e($phone) ?></a></li>
      <?php endif; ?>
      <?php if ($email !== ''): ?>
        <li><span class="dot dot-ok"></span> Email : <a href="mailto:<?= Security::e($email) ?>" style="color:var(--mbw-red)"><?= Security::e($email) ?></a></li>
      <?php endif; ?>
      <?php if ($address !== ''): ?>
        <li><span class="dot dot-ok"></span> Adresse : <?= Security::e($address) ?></li>
      <?php endif; ?>
      <?php if ($hours !== ''): ?>
        <li><span class="dot dot-ok"></span> Horaires : <?= Security::e($hours) ?></li>
      <?php endif; ?>
      <?php if ($phone === '' && $email === '' && $address === '' && $hours === '' && $wa === ''): ?>
        <li><span class="dot dot-ko"></span> Les coordonnées seront renseignées depuis le back-office (Phase 7).</li>
      <?php endif; ?>
    </ul>
  </div>

  <?php if ($instagram !== '' || $facebook !== '' || $tiktok !== ''): ?>
  <div class="card-mbw mt-3">
    <h3 style="font-size:1rem;margin:0 0 .75rem">Réseaux sociaux</h3>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <?php if ($instagram !== ''): ?><a class="btn-mbw btn-dark btn-sm" href="<?= Security::e($instagram) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
      <?php if ($facebook !== ''): ?><a class="btn-mbw btn-dark btn-sm" href="<?= Security::e($facebook) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
      <?php if ($tiktok !== ''): ?><a class="btn-mbw btn-dark btn-sm" href="<?= Security::e($tiktok) ?>" target="_blank" rel="noopener">TikTok</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <a class="btn-mbw btn-ghost mt-4" href="<?= Url::to('shop') ?>">← Retour à la boutique</a>
</section>
