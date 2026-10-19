<?php
/**
 * Liste des commandes admin.
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var array $filters
 * @var array $statusCounts
 * @var array $statusLabels
 * @var string $whatsappNumber
 */
use App\Helpers\Security;
use App\Helpers\Url;

$f = $filters ?? [];
$badge = static fn (string $s): string => 'admin-badge admin-badge--' . strtolower($s);
$qs = static function (array $over) use ($f): string {
    $q = array_merge(['status' => $f['status'] ?? '', 'q' => $f['q'] ?? '', 'page' => 1], $over);
    if (!empty($f['archived'])) {
        $q['archived'] = '1';
    }
    $q = array_filter($q, static fn ($v) => $v !== '' && $v !== null);
    return $q === [] ? '' : '?' . http_build_query($q);
};

/** Lien wa.me vers le client (message pré-rempli avec référence). */
$waLink = static function (array $o) use ($whatsappNumber): string {
    $phone = preg_replace('/\D+/', '', (string) $o['customer_phone']);
    if ($phone === null || $phone === '') {
        return '';
    }
    $text = 'Bonjour ' . $o['customer_name'] . ', au sujet de votre commande '
        . $o['reference'] . ' (' . number_format((int) $o['total'], 0, ',', ' ') . ' FCFA).';
    $to = str_starts_with($phone, '221') ? $phone : '221' . $phone;
    return 'https://wa.me/' . $to . '?text=' . rawurlencode($text);
};
$showArchived = !empty($f['archived']);
?>
<div class="admin-pagehead">
  <div>
    <h1><?= $showArchived ? 'Commandes archivées' : 'Commandes' ?></h1>
    <p><?= (int) $total ?> commande(s)</p>
  </div>
  <?php if ($showArchived): ?>
    <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('orders') ?>">← Actives</a>
  <?php endif; ?>
</div>

<form method="get" action="<?= Url::admin('orders') ?>" class="admin-searchbar">
  <input type="search" name="q" value="<?= Security::e($f['q'] ?? '') ?>" placeholder="Référence, nom, téléphone…" data-admin-search>
  <?php if (!empty($f['status'])): ?><input type="hidden" name="status" value="<?= Security::e($f['status']) ?>"><?php endif; ?>
  <?php if ($showArchived): ?><input type="hidden" name="archived" value="1"><?php endif; ?>
  <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">OK</button>
</form>

<?php if (!$showArchived): ?>
<div class="admin-chips">
  <a class="admin-chip <?= empty($f['status']) ? 'is-active' : '' ?>" href="<?= Url::admin('orders') ?>">
    Tous <span class="admin-chip__count"><?= array_sum($statusCounts) ?></span>
  </a>
  <?php foreach ($statusLabels as $val => $label): ?>
    <?php $n = (int) ($statusCounts[$val] ?? 0); if ($n === 0 && $val !== 'NEW') continue; ?>
    <a class="admin-chip <?= ($f['status'] ?? '') === $val ? 'is-active' : '' ?>" href="<?= Url::admin('orders' . $qs(['status' => $val])) ?>">
      <?= Security::e($label) ?> <span class="admin-chip__count"><?= $n ?></span>
    </a>
  <?php endforeach; ?>
  <a class="admin-chip" href="<?= Url::admin('orders?archived=1') ?>">📦 Archivées</a>
</div>
<?php endif; ?>

<?php if ($items === []): ?>
  <div class="admin-card admin-empty">
    <div class="admin-empty__icon">🧾</div>
    <h3><?= $showArchived ? 'Aucune commande archivée' : 'Aucune commande' ?></h3>
    <p><?= $showArchived ? 'Les commandes archivées apparaîtront ici.' : 'Les commandes clients apparaîtront ici.' ?></p>
  </div>
<?php else: ?>
  <?php foreach ($items as $o): ?>
    <div class="admin-card">
      <a class="admin-card--link" href="<?= Url::admin('orders/' . (int) $o['id']) ?>" style="text-decoration:none;display:block">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
          <div style="min-width:0">
            <div style="font-weight:700;color:#fff;font-size:.9rem"><?= Security::e((string) $o['reference']) ?><?= !empty($o['archived_at']) ? ' <span style="color:#6b7280">📦</span>' : '' ?></div>
            <div class="admin-row__meta">
              <?= Security::e((string) $o['customer_name']) ?> · <?= Security::e((string) $o['customer_phone']) ?>
            </div>
            <div class="admin-row__meta">
              <?= Security::e(\App\Helpers\Format::date((string) $o['created_at'], 'd/m/Y H:i')) ?> ·
              <?= (int) $o['items_count'] ?> article(s)
            </div>
          </div>
          <div style="text-align:right;flex:none">
            <span class="<?= $badge((string) $o['status']) ?>"><?= Security::e($statusLabels[$o['status']] ?? $o['status']) ?></span>
            <div style="margin-top:.35rem;font-weight:800;color:#fff"><?= number_format((int) $o['total'], 0, ',', ' ') ?> FCFA</div>
          </div>
        </div>
      </a>
      <?php if (empty($o['archived_at'])): ?>
      <div class="admin-order-actions">
        <?php $wa = $waLink($o); if ($wa !== ''): ?>
          <a class="admin-btn admin-btn--sm admin-btn--wa" href="<?= Security::e($wa) ?>" target="_blank" rel="noopener" data-confirm-click="Ouvrir WhatsApp pour contacter <?= Security::e((string) $o['customer_name']) ?> ?">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.5 14.1c-.2.7-1.3 1.3-2.1 1.4-.6.1-1.3.1-2.1-.1a12 12 0 0 1-6.5-5.7c-.6-1.1-.6-2.2 0-3.3.2-.4.6-.9 1-.9h.7c.2 0 .5-.1.8.6l.9 2.1c.1.2.1.4 0 .6l-.4.6c-.1.2-.3.4-.1.7a9 9 0 0 0 3.8 3.4c.3.1.5.1.7-.1l.9-1c.2-.2.4-.3.7-.2l2.1 1c.3.1.5.3.6.4.1.2.1.6-.1 1.1z"/></svg>
            WhatsApp
          </a>
        <?php endif; ?>
        <a class="admin-btn admin-btn--sm admin-btn--dark" href="tel:<?= Security::e('221' . preg_replace('/\D+/', '', (string) $o['customer_phone'])) ?>">
          📞 Appeler
        </a>
        <?php if ($o['status'] === 'NEW'): ?>
          <form method="post" action="<?= Url::admin('orders/' . (int) $o['id'] . '/confirm') ?>" style="margin:0">
            <?= Security::csrfField() ?>
            <button class="admin-btn admin-btn--sm admin-btn--primary" type="submit">✓ Confirmer</button>
          </form>
        <?php endif; ?>
        <a class="admin-btn admin-btn--sm admin-btn--ghost" href="<?= Url::admin('orders/' . (int) $o['id']) ?>">Détail</a>
      </div>
      <?php else: ?>
      <div class="admin-order-actions">
        <form method="post" action="<?= Url::admin('orders/' . (int) $o['id'] . '/unarchive') ?>" style="margin:0">
          <?= Security::csrfField() ?>
          <button class="admin-btn admin-btn--sm admin-btn--ghost" type="submit">↩ Restaurer</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php if ($pages > 1): ?>
    <div class="admin-pagination">
      <?php if ($page > 1): ?><a href="<?= Url::admin('orders' . $qs(['page' => $page - 1])) ?>">← Préc.</a><?php endif; ?>
      <span>Page <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= Url::admin('orders' . $qs(['page' => $page + 1])) ?>">Suiv. →</a><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
