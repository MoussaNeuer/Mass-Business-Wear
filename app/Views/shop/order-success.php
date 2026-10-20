<?php
/**
 * Page succès — Phase 4.
 * @var ?array $order
 * @var array  $items
 * @var string $currency
 * @var string $whatsapp  URL wa.me (déjà encodée) si commande trouvée
 */
use App\Helpers\Security;
use App\Helpers\Url;
use App\Repositories\AdminOrderRepository;

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
    $statusLabel = AdminOrderRepository::STATUS_LABELS[(string) $order['status']] ?? (string) $order['status'];
    $customerPhone = preg_replace('/\D+/', '', (string) $order['customer_phone']) ?? '';
?>
<section class="mt-4" id="order-success">
  <div class="card-mbw" style="text-align:center;padding:1.5rem 1rem">
    <div style="font-size:3rem;line-height:1" aria-hidden="true">✅</div>
    <h1 style="font-size:1.5rem;font-weight:800;margin:.75rem 0 .25rem">Commande reçue !</h1>
    <p class="text-muted" style="margin:0 0 .5rem">Merci <strong><?= Security::e((string) $order['customer_name']) ?></strong>, votre panier est enregistré.</p>
    <div style="display:inline-block;background:var(--surface-2);border:1px dashed var(--border);border-radius:var(--radius-sm);padding:.5rem 1rem;margin:.5rem 0">
      <span class="text-muted" style="font-size:.75rem;display:block">Référence</span>
      <strong style="font-size:1.15rem;letter-spacing:.04em"><?= Security::e((string) $order['reference']) ?></strong>
    </div>
  </div>

  <div class="card-mbw mt-3">
    <h2 style="font-size:.95rem;font-weight:700;margin:0 0 .5rem">Récapitulatif</h2>
    <?php foreach ($itemsList as $it):
        $meta = trim(($it['size'] ?? '') . ' ' . ($it['color'] ?? ''));
        $line = (int) $it['unit_price'] * (int) $it['quantity'];
    ?>
      <div style="display:flex;justify-content:space-between;gap:.5rem;padding:.35rem 0;font-size:.9rem;border-bottom:1px solid var(--border)">
        <span>
          <?= Security::e((string) $it['product_name']) ?>
          <?php if ($meta !== ''): ?><span class="text-muted">(<?= Security::e($meta) ?>)</span><?php endif; ?>
          <span class="text-muted">×<?= (int) $it['quantity'] ?></span>
        </span>
        <span style="font-weight:700;white-space:nowrap"><?= number_format($line, 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
      </div>
    <?php endforeach; ?>

    <div style="display:flex;justify-content:space-between;padding:.35rem 0;font-size:.9rem">
      <span class="text-muted">Sous-total</span>
      <span><?= number_format((int) $order['subtotal'], 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:.35rem 0;font-size:.9rem">
      <span class="text-muted">Livraison</span>
      <span>
        <?php if ((int) $order['shipping'] > 0): ?>
          <?= number_format((int) $order['shipping'], 0, ',', ' ') ?> <?= Security::e($currency) ?>
        <?php else: ?>
          Offerte
        <?php endif; ?>
      </span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:.5rem 0 0;border-top:1px solid var(--border);font-weight:800">
      <span>Total</span>
      <span><?= number_format((int) $order['total'], 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
    </div>

    <div class="text-muted" style="font-size:.85rem;margin-top:.75rem">
      <div><strong>Zone :</strong> <?= Security::e((string) $order['zone']) ?></div>
      <div><strong>Adresse :</strong> <?= Security::e((string) $order['address']) ?></div>
      <div><strong>Téléphone :</strong> <?= $customerPhone !== '' ? '221 ' . Security::e($customerPhone) : '—' ?></div>
      <div><strong>Statut :</strong> <?= Security::e($statusLabel) ?></div>
      <div><strong>Paiement :</strong> à la livraison</div>
    </div>
  </div>

  <?php if (!empty($whatsapp)): ?>
    <div class="card-mbw mt-3" style="border-color:#25D366;background:#25D36612">
      <h2 style="font-size:.95rem;font-weight:700;margin:0 0 .5rem">Étape suivante : WhatsApp</h2>
      <p class="text-muted" style="font-size:.88rem;margin:0 0 .75rem">
        Validez votre commande sur WhatsApp. Le message est déjà pré-rempli avec le
        <strong>détail de vos articles, vos coordonnées, l'adresse de livraison et le montant total</strong> :
        vous n'avez rien à retaper. Notre équipe confirme la disponibilité et vous indique le créneau de livraison.
      </p>
      <a class="pdp__wa" style="margin-top:0" href="<?= Security::e($whatsapp) ?>" target="_blank" rel="noopener">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.32 4.96L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24 2.2 0 4.27.86 5.83 2.42a8.19 8.19 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.25 8.23Zm4.52-6.17c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.16.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.13-.15.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.65.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.1-.22-.16-.47-.29Z"/>
        </svg>
        Confirmer sur WhatsApp
      </a>
      <p class="text-muted" style="font-size:.78rem;margin:.6rem 0 0;text-align:center">
        La commande n'est définitive qu'après la confirmation de notre équipe.
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
  // Vider le panier local une fois la commande confirmée côté serveur
  document.addEventListener('DOMContentLoaded', function () {
    try {
      if (window.MBW && window.MBW.cart) window.MBW.cart.clear();
      else localStorage.removeItem('mbw_cart_v1');
    } catch (e) { /* ignore */ }
  });
</script>
<?php endif; ?>
