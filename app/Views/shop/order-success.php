<?php
/**
 * Page succès — récapitulatif complet avant la confirmation WhatsApp.
 * @var ?array  $order
 * @var array   $items
 * @var array   $photos
 * @var string  $currency
 * @var string  $whatsapp  URL wa.me (déjà encodée) si commande trouvée
 */
use App\Helpers\Security;
use App\Helpers\Url;
use App\Repositories\AdminOrderRepository;
use App\Services\ImageService;

if ($order === null):
?>
<section class="mt-4 empty-state">
  <div class="icon" aria-hidden="true">📦</div>
  <h1 style="font-size:1.4rem;font-weight:800;margin:.5rem 0">Commande introuvable</h1>
  <p class="text-muted">Cette référence n'existe pas ou le lien a expiré.</p>
  <a class="btn-mbw btn-primary mt-3" href="<?= Url::to('shop') ?>">Retour à la boutique</a>
</section>
<?php else:
    $itemsList = $items ?? [];
    $photosList = $photos ?? [];
    $statusLabel = AdminOrderRepository::STATUS_LABELS[(string) $order['status']] ?? (string) $order['status'];
    $customerPhone = preg_replace('/\D+/', '', (string) $order['customer_phone']) ?? '';
    $totalQty = 0;
    foreach ($itemsList as $it) {
        $totalQty += (int) $it['quantity'];
    }
    $formattedDate = '';
    $ts = strtotime((string) $order['created_at']);
    if ($ts !== false) {
        $formattedDate = date('d/m/Y à H:i', $ts);
    }
?>
<section class="mt-4" id="order-success">

  <!-- ------------------------------------------------------- En-tête -->
  <div class="card-mbw" style="text-align:center;padding:1.5rem 1rem">
    <div style="font-size:3rem;line-height:1" aria-hidden="true">✅</div>
    <h1 style="font-size:1.5rem;font-weight:800;margin:.75rem 0 .25rem">Commande reçue !</h1>
    <p class="text-muted" style="margin:0 0 .5rem">
      Merci <strong><?= Security::e((string) $order['customer_name']) ?></strong>,
      votre commande est enregistrée.
    </p>
    <div style="display:inline-block;background:var(--surface-2);border:1px dashed var(--border);border-radius:var(--radius-sm);padding:.5rem 1rem;margin:.5rem 0">
      <span class="text-muted" style="font-size:.75rem;display:block">Référence</span>
      <strong style="font-size:1.15rem;letter-spacing:.04em"><?= Security::e((string) $order['reference']) ?></strong>
    </div>
    <?php if ($formattedDate !== ''): ?>
      <p class="text-muted" style="font-size:.82rem;margin:.25rem 0 0">
        Passée le <?= Security::e($formattedDate) ?>
        <?php if ($totalQty > 0): ?> · <?= $totalQty ?> article(s)<?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- ------------------------------------------------ Vos articles -->
  <div class="card-mbw mt-3">
    <h2 style="font-size:.95rem;font-weight:700;margin:0 0 .75rem">Vos articles</h2>

    <?php if ($itemsList === []): ?>
      <p class="text-muted" style="font-size:.88rem;margin:0">Aucun article.</p>
    <?php endif; ?>

    <?php foreach ($itemsList as $it):
        $meta = trim(($it['size'] ?? '') . ' ' . ($it['color'] ?? ''));
        $qty = (int) $it['quantity'];
        $unit = (int) $it['unit_price'];
        $line = $unit * $qty;
        $imageFile = (string) ($it['image'] ?? '');
    ?>
      <div class="os-item">
        <div class="os-item__thumb">
          <?php if ($imageFile !== ''): ?>
            <img src="<?= Security::e(ImageService::thumbUrl($imageFile)) ?>"
                 alt="<?= Security::e((string) $it['product_name']) ?>"
                 loading="lazy" width="56" height="70">
          <?php else: ?>
            <span aria-hidden="true">👕</span>
          <?php endif; ?>
        </div>

        <div class="os-item__body">
          <div class="os-item__name"><?= Security::e((string) $it['product_name']) ?></div>
          <?php if ($meta !== ''): ?>
            <div class="os-item__meta"><?= Security::e($meta) ?></div>
          <?php endif; ?>
          <div class="os-item__meta">
            <?= $qty ?> × <?= number_format($unit, 0, ',', ' ') ?> <?= Security::e($currency) ?>
          </div>
        </div>

        <div class="os-item__total"><?= number_format($line, 0, ',', ' ') ?> <?= Security::e($currency) ?></div>
      </div>
    <?php endforeach; ?>

    <!-- ------------------------------------------------ Totaux -->
    <div class="os-totals">
      <div class="os-totals__row">
        <span class="text-muted">Articles (<?= count($itemsList) ?>)</span>
        <span><?= number_format((int) $order['subtotal'], 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
      </div>
      <div class="os-totals__row">
        <span class="text-muted">Livraison</span>
        <span>
          <?php if ((int) $order['shipping'] > 0): ?>
            <?= number_format((int) $order['shipping'], 0, ',', ' ') ?> <?= Security::e($currency) ?>
          <?php else: ?>
            Offerte
          <?php endif; ?>
        </span>
      </div>
      <div class="os-totals__row os-totals__row--grand">
        <span>Total à payer</span>
        <span><?= number_format((int) $order['total'], 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
      </div>
      <div class="os-totals__note">Paiement à la livraison — vous ne payez rien en ligne.</div>
    </div>
  </div>

  <!-- ------------------------------------ Photos de référence (client) -->
  <?php if ($photosList !== []): ?>
    <div class="card-mbw mt-3">
      <h2 style="font-size:.95rem;font-weight:700;margin:0 0 .25rem">Vos photos de référence</h2>
      <p class="text-muted" style="font-size:.82rem;margin:0 0 .75rem">
        <?= count($photosList) ?> photo(s) jointe(s) à votre commande.
        Touchez pour agrandir.
      </p>
      <div class="os-gallery">
        <?php foreach ($photosList as $ph): ?>
          <button type="button" class="os-gallery__item"
                  data-os-zoom="<?= Security::e(ImageService::orderUrl((string) $ph['file'])) ?>"
                  aria-label="Agrandir la photo">
            <img src="<?= Security::e(ImageService::orderUrl((string) $ph['file'])) ?>"
                 alt="Photo de référence jointe à la commande"
                 loading="lazy">
            <span class="os-gallery__zoom" aria-hidden="true">＋</span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- ------------------------------------------------- Détails -->
  <div class="card-mbw mt-3">
    <h2 style="font-size:.95rem;font-weight:700;margin:0 0 .5rem">Détails de la commande</h2>
    <dl class="os-dl">
      <div class="os-dl__row">
        <dt>Référence</dt>
        <dd><?= Security::e((string) $order['reference']) ?></dd>
      </div>
      <?php if ($formattedDate !== ''): ?>
        <div class="os-dl__row">
          <dt>Date</dt>
          <dd><?= Security::e($formattedDate) ?></dd>
        </div>
      <?php endif; ?>
      <div class="os-dl__row">
        <dt>Nom</dt>
        <dd><?= Security::e((string) $order['customer_name']) ?></dd>
      </div>
      <div class="os-dl__row">
        <dt>Téléphone</dt>
        <dd><?= $customerPhone !== '' ? '221 ' . Security::e($customerPhone) : '—' ?></dd>
      </div>
      <div class="os-dl__row">
        <dt>Zone de livraison</dt>
        <dd><?= Security::e((string) $order['zone']) ?></dd>
      </div>
      <div class="os-dl__row">
        <dt>Adresse</dt>
        <dd><?= Security::e((string) $order['address']) ?></dd>
      </div>
      <div class="os-dl__row">
        <dt>Paiement</dt>
        <dd>À la livraison</dd>
      </div>
      <div class="os-dl__row">
        <dt>Statut</dt>
        <dd><span class="os-status"><?= Security::e($statusLabel) ?></span></dd>
      </div>
      <?php if (trim((string) ($order['notes'] ?? '')) !== ''): ?>
        <div class="os-dl__row os-dl__row--full">
          <dt>Votre note</dt>
          <dd><?= nl2br(Security::e((string) $order['notes']), false) ?></dd>
        </div>
      <?php endif; ?>
    </dl>
  </div>

  <!-- ------------------------------------------- Étape suivante -->
  <?php if (!empty($whatsapp)): ?>
    <div class="card-mbw mt-3 os-wa">
      <h2 style="font-size:.95rem;font-weight:700;margin:0 0 .5rem">Dernière étape : confirmer sur WhatsApp</h2>
      <p class="text-muted" style="font-size:.88rem;margin:0 0 .75rem">
        Le récapitulatif ci-dessus sera envoyé à notre équipe, <strong>déjà pré-rempli</strong> :
        articles, coordonnées, adresse et montant total. Vous n'avez rien à retaper.
      </p>
      <ol class="os-steps">
        <li>Votre commande part sur WhatsApp</li>
        <li>Notre équipe vérifie la <strong>disponibilité</strong></li>
        <li>Nous vous confirmons le <strong>créneau de livraison</strong></li>
      </ol>
      <a class="pdp__wa" style="margin-top:.75rem" href="<?= Security::e($whatsapp) ?>" target="_blank" rel="noopener">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.32 4.96L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24 2.2 0 4.27.86 5.83 2.42a8.19 8.19 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.25 8.23Zm4.52-6.17c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.16.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.65.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.1-.22-.16-.47-.29Z"/>
        </svg>
        Envoyer ma commande sur WhatsApp
      </a>
      <p class="text-muted" style="font-size:.78rem;margin:.6rem 0 0;text-align:center">
        Votre commande n'est définitive qu'après la confirmation de notre équipe.
      </p>
    </div>
  <?php endif; ?>

  <a class="btn-mbw btn-ghost btn-block mt-2" href="<?= Url::to('shop') ?>">Continuer mes achats</a>
  <p class="text-muted text-center" style="font-size:.8rem;margin-top:1rem">
    Conservez votre référence <strong><?= Security::e((string) $order['reference']) ?></strong>
    pour tout échange avec le service client.
  </p>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Vider le panier local une fois la commande confirmée côté serveur
    try {
      if (window.MBW && window.MBW.cart) window.MBW.cart.clear();
      else localStorage.removeItem('mbw_cart_v1');
    } catch (e) { /* ignore */ }

    // Lightbox des photos de référence (tap pour agrandir, Échap pour fermer)
    var zoomables = document.querySelectorAll('[data-os-zoom]');
    if (!zoomables.length) return;

    var box = document.createElement('div');
    box.className = 'os-lightbox';
    box.hidden = true;
    box.innerHTML = '<img alt="Photo agrandie">' +
                    '<button type="button" class="os-lightbox__close" aria-label="Fermer">✕</button>';
    document.body.appendChild(box);

    var img = box.querySelector('img');
    var close = box.querySelector('.os-lightbox__close');
    var hide = function () { box.hidden = true; };

    box.addEventListener('click', function (e) {
      if (e.target === box || e.target === close) hide();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !box.hidden) hide();
    });

    Array.prototype.forEach.call(zoomables, function (btn) {
      btn.addEventListener('click', function () {
        img.src = btn.getAttribute('data-os-zoom') || '';
        box.hidden = false;
      });
    });
  });
</script>
<?php endif; ?>
