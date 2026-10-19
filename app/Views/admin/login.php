<?php
/**
 * Connexion administration — design animé.
 * @var ?string $error
 * @var string $next
 */
use App\Helpers\Security;
use App\Helpers\Url;
$err = (string) ($error ?? '');
?>
<div class="auth-wrap auth-wrap--split">
  <div class="auth-grid" aria-hidden="true"></div>

  <aside class="auth-brand" aria-hidden="true">
    <div class="auth-brand__logo">
      <img src="<?= Url::asset('img/logo.jpeg') ?>" alt="" width="96" height="96">
    </div>
    <p class="auth-brand__kicker">Back-office</p>
    <h2 class="auth-brand__title">MASS <span>BUSINESS<br>WEAR</span></h2>
    <p class="auth-brand__text">
      Gérez commandes, produits, stock et clients depuis un seul tableau de bord — sur mobile comme sur desktop.
    </p>
    <ul class="auth-brand__list">
      <li><span class="tick" aria-hidden="true">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </span>Commandes traitées en un tap</li>
      <li><span class="tick" aria-hidden="true">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </span>Stock et ventes en temps réel</li>
      <li><span class="tick" aria-hidden="true">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </span>PWA installable, hors ligne</li>
    </ul>
    <p class="auth-brand__foot">Sénégal · Dakar</p>
  </aside>

  <section class="auth-panel">
    <div class="auth-card<?= $err !== '' ? ' has-error' : '' ?>">
      <span class="auth-secure">
        <span class="dot" aria-hidden="true"></span>
        Accès sécurisé
      </span>

      <h1 class="auth-title">Connexion</h1>
      <p class="auth-sub">Identifiez-vous pour accéder à l'espace administrateur.</p>

      <?php if ($err !== ''): ?>
        <div class="auth-alert" role="alert">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <span><?= Security::e($err) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= Url::admin('login') ?>" autocomplete="on" data-auth-form novalidate>
        <?= Security::csrfField() ?>
        <input type="hidden" name="next" value="<?= Security::e($next ?? '') ?>">

        <div class="auth-field">
          <span class="auth-field__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <input id="login" name="login" type="text" required placeholder=" "
                 autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                 value="<?= Security::e($_POST['login'] ?? '') ?>">
          <label for="login">Identifiant</label>
        </div>

        <div class="auth-field auth-field--pass">
          <span class="auth-field__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input id="password" name="password" type="password" required placeholder=" "
                 autocomplete="current-password" data-auth-pass>
          <label for="password">Mot de passe</label>
          <button class="auth-toggle" type="button" data-auth-eye aria-label="Afficher le mot de passe" hidden>
            <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="eye-closed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden>
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>

        <button class="auth-submit" type="submit">
          <span class="auth-spinner" aria-hidden="true"></span>
          <span class="auth-submit__label">Se connecter</span>
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
          </svg>
        </button>
      </form>

      <p class="auth-foot"><a href="<?= Url::to('/') ?>">← Retour à la boutique</a></p>
    </div>
  </section>
</div>

<script>
(function () {
  var eye = document.querySelector('[data-auth-eye]');
  var pass = document.querySelector('[data-auth-pass]');
  if (eye && pass) {
    var eyeOpen = eye.querySelector('.eye-open');
    var eyeClosed = eye.querySelector('.eye-closed');
    var setVis = function (el, show) {
      // .hidden n'existe pas sur SVGElement : passer par l'attribut
      if (show) { el.removeAttribute('hidden'); } else { el.setAttribute('hidden', ''); }
    };
    eye.hidden = false;
    setVis(eyeClosed, false);
    eye.addEventListener('click', function () {
      var show = pass.type === 'password';
      pass.type = show ? 'text' : 'password';
      eye.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
      setVis(eyeOpen, !show);
      setVis(eyeClosed, show);
      pass.focus();
    });
  }

  var form = document.querySelector('[data-auth-form]');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
        return;
      }
      var btn = form.querySelector('.auth-submit');
      if (btn && !btn.classList.contains('is-loading')) {
        btn.classList.add('is-loading');
        btn.setAttribute('aria-busy', 'true');
      }
    });
  }

})();
</script>
