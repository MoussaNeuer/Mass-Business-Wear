<?php
/**
 * Catégories admin — liste + création + édition inline.
 * @var array $categories
 */
use App\Helpers\Security;
use App\Helpers\Url;
?>
<div class="admin-pagehead">
  <div>
    <h1>Catégories</h1>
    <p><?= count($categories) ?> catégorie(s)</p>
  </div>
</div>

<!-- Création -->
<form method="post" action="<?= Url::admin('categories') ?>" class="admin-card">
  <div class="admin-card__head"><h2 class="admin-card__title">Nouvelle catégorie</h2></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
    <div class="admin-field">
      <label for="c-name">Nom *</label>
      <input id="c-name" name="name" required maxlength="80" placeholder="Ex. Vestes">
    </div>
    <div class="admin-field">
      <label for="c-slug">Slug</label>
      <input id="c-slug" name="slug" maxlength="80" placeholder="auto">
    </div>
  </div>
  <div class="admin-field">
    <label for="c-desc">Description</label>
    <textarea id="c-desc" name="description" rows="2"></textarea>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
    <div class="admin-field">
      <label for="c-sort">Ordre</label>
      <input id="c-sort" name="sort_order" type="number" value="0">
    </div>
    <label class="admin-check" style="align-self:end">
      <input type="checkbox" name="is_active" value="1" checked> Active
    </label>
  </div>
  <button class="admin-btn admin-btn--primary admin-btn--sm" type="submit">Créer</button>
</form>

<?php foreach ($categories as $c): ?>
  <details class="admin-card">
    <summary style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:.5rem;list-style:none">
      <div>
        <strong style="color:#fff;font-size:.92rem"><?= Security::e((string) $c['name']) ?></strong>
        <div class="admin-row__meta"><?= Security::e((string) $c['slug']) ?> · <?= (int) $c['products_count'] ?> produit(s)</div>
      </div>
      <span class="admin-badge <?= !empty($c['is_active']) ? 'admin-badge--active' : 'admin-badge--archived' ?>"><?= !empty($c['is_active']) ? 'Active' : 'Inactive' ?></span>
    </summary>

    <form method="post" action="<?= Url::admin('categories/' . (int) $c['id']) ?>" style="margin-top:.8rem">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <div class="admin-field">
          <label>Nom</label>
          <input name="name" required maxlength="80" value="<?= Security::e((string) $c['name']) ?>">
        </div>
        <div class="admin-field">
          <label>Slug</label>
          <input name="slug" maxlength="80" value="<?= Security::e((string) $c['slug']) ?>">
        </div>
      </div>
      <div class="admin-field">
        <label>Description</label>
        <textarea name="description" rows="2"><?= Security::e((string) ($c['description'] ?? '')) ?></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <div class="admin-field">
          <label>Ordre</label>
          <input name="sort_order" type="number" value="<?= (int) $c['sort_order'] ?>">
        </div>
        <label class="admin-check" style="align-self:end">
          <input type="checkbox" name="is_active" value="1" <?= !empty($c['is_active']) ? 'checked' : '' ?>> Active
        </label>
      </div>
      <div class="admin-btn-row">
        <button class="admin-btn admin-btn--primary admin-btn--sm" type="submit">Enregistrer</button>
      </div>
    </form>

    <form method="post" action="<?= Url::admin('categories/' . (int) $c['id'] . '/delete') ?>" style="margin-top:.6rem"
          data-confirm="Supprimer la catégorie « <?= Security::e((string) $c['name']) ?> » ? Les produits ne seront pas supprimés.">
      <button class="admin-btn admin-btn--danger admin-btn--sm" type="submit">Supprimer</button>
    </form>
  </details>
<?php endforeach; ?>
