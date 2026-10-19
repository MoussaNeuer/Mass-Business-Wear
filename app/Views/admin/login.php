<?php
/**
 * @var ?string $error
 * @var string $next
 */
use App\Helpers\Security;
use App\Helpers\Url;
?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="text-center mb-4">
      <img src="<?= Url::asset('img/logo.jpeg') ?>" alt="" width="88" height="88" style="border-radius:20px;background:#000">
      <h1 style="color:var(--mbw-white);font-size:1.3rem;margin:.75rem 0 0">MASS <span class="text-red">BUSINESS WEAR</span></h1>
      <p class="text-muted" style="font-size:.9rem">Espace administrateur</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-mbw alert-error" role="alert"><?= Security::e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= Url::admin('login') ?>" autocomplete="on">
      <?= Security::csrfField() ?>
      <input type="hidden" name="next" value="<?= Security::e($next ?? '') ?>">

      <div class="field">
        <label for="login">Identifiant</label>
        <input id="login" name="login" type="text" required autocomplete="username"
               autocapitalize="none" autocorrect="off" spellcheck="false"
               value="<?= Security::e($_POST['login'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
      </div>

      <button class="btn-mbw btn-primary btn-block mt-3" type="submit">Se connecter</button>
    </form>

    <p class="text-center mt-4" style="font-size:.8rem">
      <a href="<?= Url::to('/') ?>" class="text-red" style="text-decoration:none">← Retour à la boutique</a>
    </p>
  </div>
</div>
