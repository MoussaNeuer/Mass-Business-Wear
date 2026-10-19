<?php
/**
 * Page légale générique (CGV / confidentialité).
 * @var string $heading
 * @var string $updated
 * @var list<array{0:string,1:string}> $sections
 */
use App\Helpers\Security;
use App\Helpers\Url;
?>
<section class="mt-4">
  <h1 style="font-size:1.5rem;font-weight:800"><?= Security::e($heading) ?></h1>
  <p class="text-muted" style="font-size:.85rem">Dernière mise à jour : <?= Security::e($updated) ?></p>

  <?php foreach ($sections as $i => $sec): ?>
    <div class="card-mbw mt-3">
      <h3 style="font-size:1rem;margin:0 0 .6rem">
        <?= $i + 1 ?>. <?= Security::e($sec[0]) ?>
      </h3>
      <p style="font-size:.9rem;line-height:1.7;margin:0"><?= Security::e($sec[1]) ?></p>
    </div>
  <?php endforeach; ?>

  <div style="display:flex;gap:.5rem;flex-wrap:wrap" class="mt-4">
    <a class="btn-mbw btn-ghost btn-sm" href="<?= Url::to('about') ?>">À propos</a>
    <a class="btn-mbw btn-ghost btn-sm" href="<?= Url::to('legal/terms') ?>">CGV</a>
    <a class="btn-mbw btn-ghost btn-sm" href="<?= Url::to('legal/privacy') ?>">Confidentialité</a>
    <a class="btn-mbw btn-ghost btn-sm" href="<?= Url::to('contact') ?>">Contact</a>
  </div>
</section>
