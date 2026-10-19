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
?>
<section class="mt-4" id="order-success">
  <div class="card-mbw" style="text-align:center;padding:1.5rem 1rem">
    <div style="font-size:3rem;line-height:1" aria-hidden="true">✅</div>
    <h1 style="font-size:1.5rem;font-weight:800;margin:.75rem 0 .25rem">Commande reçue !</h1>
    <p class="text-muted" style="margin:0 0 .5rem">Merci <strong><?= Security::e((string) $order['customer_name']) ?></strong>, nous vous confirmons sur WhatsApp.</p>
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
      <span class="text-muted">Livraison</span>
      <span><?= number_format((int) $order['shipping'], 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;padding:.5rem 0 0;border-top:1px solid var(--border);font-weight:800">
      <span>Total</span>
      <span><?= number_format((int) $order['total'], 0, ',', ' ') ?> <?= Security::e($currency) ?></span>
    </div>

    <div class="text-muted" style="font-size:.85rem;margin-top:.75rem">
      <div><strong>Zone :</strong> <?= Security::e((string) $order['zone']) ?></div>
      <div><strong>Adresse :</strong> <?= Security::e((string) $order['address']) ?></div>
      <div><strong>Statut :</strong> <?= Security::e((string) $order['status']) ?></div>
    </div>
  </div>

  <?php if (!empty($whatsapp)): ?>
    <a class="btn-mbw btn-primary btn-block mt-3" href="<?= Security::e($whatsapp) ?>" target="_blank" rel="noopener">
      Confirmer sur WhatsApp
    </a>
  <?php endif; ?>

  <a class="btn-mbw btn-ghost btn-block mt-2" href="<?= Url::to('shop') ?>">Continuer mes achats</a>
  <p class="text-muted text-center" style="font-size:.8rem;margin-top:1rem">
    Conservez votre référence pour tout échange avec le service client.
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
