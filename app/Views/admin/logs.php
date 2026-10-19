<?php
/**
 * Journal des actions admin.
 * @var array $items
 * @var int $total
 * @var int $pages
 * @var int $page
 * @var array $filters
 * @var array $admins
 */
use App\Helpers\Security;
use App\Helpers\Url;

$f = $filters ?? [];
$qs = static function (array $over) use ($f): string {
    $q = array_merge(['admin_id' => $f['admin_id'] ?? 0, 'page' => 1], $over);
    $q = array_filter($q, static fn ($v) => $v !== 0 && $v !== '' && $v !== null);
    return $q === [] ? '' : '?' . http_build_query($q);
};
?>
<div class="admin-pagehead">
  <div>
    <h1>Journal</h1>
    <p><?= (int) $total ?> entrée(s)</p>
  </div>
  <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('logs') ?>">Réinitialiser</a>
</div>

<form method="get" action="<?= Url::admin('logs') ?>" class="admin-searchbar" style="max-width:260px">
  <select name="admin_id" data-auto-submit style="min-height:44px;padding:.5rem .8rem;border-radius:12px;border:1px solid rgba(255,255,255,.1);background:#0c0c0e;color:#fff;font:inherit;flex:1">
    <option value="0">Tous les admins</option>
    <?php foreach ($admins as $a): ?>
      <option value="<?= (int) $a['id'] ?>" <?= (int) ($f['admin_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>>
        <?= Security::e((string) $a['login']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php if ($items === []): ?>
  <div class="admin-card admin-empty">
    <div class="admin-empty__icon">📋</div>
    <h3>Journal vide</h3>
    <p>Les actions admin seront enregistrées ici.</p>
  </div>
<?php else: ?>
  <div class="admin-card">
    <?php foreach ($items as $log): ?>
      <div class="admin-row" style="cursor:default">
        <div class="admin-row__body">
          <div class="admin-row__title" style="font-size:.82rem">
            <span class="admin-badge admin-badge--new" style="font-size:.6rem;padding:.15rem .4rem"><?= Security::e((string) $log['action']) ?></span>
          </div>
          <?php if (!empty($log['details'])): ?>
            <div class="admin-row__meta"><?= Security::e((string) $log['details']) ?></div>
          <?php endif; ?>
          <div class="admin-row__meta">
            <?= Security::e(\App\Helpers\Format::date((string) $log['created_at'], 'd/m H:i:s')) ?>
            <?php if (!empty($log['admin_login'])): ?> · <?= Security::e((string) $log['admin_login']) ?><?php endif; ?>
            <?php if (!empty($log['ip'])): ?> · <?= Security::e((string) $log['ip']) ?><?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <div class="admin-pagination">
      <?php if ($page > 1): ?><a href="<?= Url::admin('logs' . $qs(['page' => $page - 1])) ?>">← Préc.</a><?php endif; ?>
      <span>Page <?= $page ?> / <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= Url::admin('logs' . $qs(['page' => $page + 1])) ?>">Suiv. →</a><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
