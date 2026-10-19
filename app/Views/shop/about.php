<?php
/**
 * À propos de la boutique.
 * @var string $shopName
 * @var string $phone
 * @var string $email
 * @var string $address
 * @var string $hours
 */
use App\Helpers\Security;
use App\Helpers\Url;
?>
<section class="mt-4">
  <h1 style="font-size:1.5rem;font-weight:800">À propos</h1>
  <p class="text-muted"><?= Security::e($shopName) ?> — prête-à-porter premium à Dakar.</p>

  <div class="card-mbw mt-3">
    <h3 style="font-size:1rem;margin:0 0 .75rem">Notre histoire</h3>
    <p style="font-size:.92rem;line-height:1.7;margin:0">
      MASS BUSINESS WEAR habille celles et ceux qui font le business au Sénégal.
      Costumes, ensembles, chemises et accessoires : des pièces nettes, confortables
      et pensées pour une journée de travail réussie — du bureau aux événements.
    </p>
  </div>

  <div class="card-mbw mt-3">
    <h3 style="font-size:1rem;margin:0 0 .75rem">Notre promesse</h3>
    <ul class="check-list">
      <li><span class="dot dot-ok"></span> Des tissus sélectionnés, une coupe soignée</li>
      <li><span class="dot dot-ok"></span> Tailles et couleurs en boutique</li>
      <li><span class="dot dot-ok"></span> Commande en quelques clics, confirmation sur WhatsApp</li>
      <li><span class="dot dot-ok"></span> Livraison rapide à Dakar et environs</li>
      <li><span class="dot dot-ok"></span> Paiement à la livraison, sans risque</li>
    </ul>
  </div>

  <div class="card-mbw mt-3">
    <h3 style="font-size:1rem;margin:0 0 .75rem">Nous trouver</h3>
    <ul class="check-list">
      <?php if ($address !== ''): ?><li><span class="dot dot-ok"></span> <?= Security::e($address) ?></li><?php endif; ?>
      <?php if ($hours !== ''): ?><li><span class="dot dot-ok"></span> <?= Security::e($hours) ?></li><?php endif; ?>
      <?php if ($phone !== ''): ?>
        <li><span class="dot dot-ok"></span> <a href="tel:<?= Security::e($phone) ?>" style="color:var(--mbw-red)"><?= Security::e($phone) ?></a></li>
      <?php endif; ?>
      <?php if ($email !== ''): ?>
        <li><span class="dot dot-ok"></span> <a href="mailto:<?= Security::e($email) ?>" style="color:var(--mbw-red)"><?= Security::e($email) ?></a></li>
      <?php endif; ?>
    </ul>
    <a class="btn-mbw btn-primary btn-block mt-3" href="<?= Url::to('contact') ?>">Nous contacter</a>
  </div>

  <a class="btn-mbw btn-ghost mt-4" href="<?= Url::to('shop') ?>">← Retour à la boutique</a>
</section>
