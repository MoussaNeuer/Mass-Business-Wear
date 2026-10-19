<?php
/**
 * Formulaire produit (create / edit) + variantes + images (Sprint B).
 * M5 : les valeurs saisies ($_POST) priment sur $product après un échec de validation.
 * @var ?array $product
 * @var array  $categories
 */
use App\Helpers\Security;
use App\Helpers\Url;
use App\Repositories\AdminProductRepository;

$isNew = $product === null;
$action = $isNew ? Url::admin('products') : Url::admin('products/' . (int) $product['id']);
$variants = $isNew ? [] : AdminProductRepository::variants((int) $product['id']);
$images = $isNew ? [] : AdminProductRepository::images((int) $product['id']);

/** Valeur POST (saisie conservée) > produit existant > défaut. */
$v = static function (string $key, string $default = '') use ($product): string {
    if (isset($_POST[$key]) && is_scalar($_POST[$key])) {
        return (string) $_POST[$key];
    }
    return isset($product[$key]) && $product[$key] !== null ? (string) $product[$key] : $default;
};
$vInt = static function (string $key, int $default = 0) use ($product): int {
    if (isset($_POST[$key]) && is_scalar($_POST[$key]) && $_POST[$key] !== '') {
        return (int) $_POST[$key];
    }
    return isset($product[$key]) && $product[$key] !== null ? (int) $product[$key] : $default;
};
$vChecked = static function (string $key) use ($product): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$key]);
    }
    return !empty($product[$key]);
};
?>
<div class="admin-pagehead">
  <div>
    <h1><?= $isNew ? 'Nouveau produit' : Security::e((string) $product['name']) ?></h1>
    <p><?= $isNew ? 'Créer un article' : 'Modifier l\'article' ?></p>
  </div>
  <a class="admin-btn admin-btn--ghost admin-btn--sm" href="<?= Url::admin('products') ?>">← Liste</a>
</div>

<form method="post" action="<?= $action ?>" class="admin-card">
  <div class="admin-field">
    <label for="p-name">Nom *</label>
    <input id="p-name" name="name" required maxlength="160" value="<?= Security::e($v('name')) ?>" placeholder="Ex. Sweat Capuche MBW">
  </div>

  <div class="admin-field">
    <label for="p-slug">Slug (URL)</label>
    <input id="p-slug" name="slug" maxlength="180" value="<?= Security::e($v('slug')) ?>" placeholder="auto si vide">
    <small>Caractères minuscules, tirets. Généré depuis le nom si vide.</small>
  </div>

  <div class="admin-field">
    <label for="p-cat">Catégorie</label>
    <select id="p-cat" name="category_id">
      <option value="0">— Aucune —</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $vInt('category_id') === (int) $c['id'] ? 'selected' : '' ?>>
          <?= Security::e((string) $c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="admin-field">
    <label for="p-desc">Description</label>
    <textarea id="p-desc" name="description" rows="4" placeholder="Matière, coupe, entretien…"><?= Security::e($v('description')) ?></textarea>
  </div>

  <div class="admin-pricegrid">
    <div class="admin-field">
      <label for="p-price">Prix (FCFA) *</label>
      <input id="p-price" name="price" type="number" inputmode="numeric" min="1" required value="<?= $vInt('price') ?: '' ?>">
    </div>
    <div class="admin-field">
      <label for="p-promo">Prix promo</label>
      <input id="p-promo" name="promo_price" type="number" inputmode="numeric" min="0" value="<?= $v('promo_price') ?>" placeholder="Vide = pas de promo">
    </div>
  </div>

  <div class="admin-field">
    <label for="p-status">Statut</label>
    <select id="p-status" name="status">
      <?php foreach (['draft' => 'Brouillon', 'active' => 'Actif (visible)', 'archived' => 'Archivé'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= $v('status', 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <label class="admin-check">
    <input type="checkbox" name="is_featured" value="1" <?= $vChecked('is_featured') ? 'checked' : '' ?>>
    Mis en avant sur l'accueil
  </label>

  <details style="margin-top:.8rem">
    <summary style="cursor:pointer;font-size:.82rem;color:#9ca3af;font-weight:700">SEO (optionnel)</summary>
    <div class="admin-field" style="margin-top:.6rem">
      <label for="p-seo-t">Titre SEO</label>
      <input id="p-seo-t" name="seo_title" maxlength="160" value="<?= Security::e($v('seo_title')) ?>">
    </div>
    <div class="admin-field">
      <label for="p-seo-d">Description SEO</label>
      <textarea id="p-seo-d" name="seo_description" rows="2" maxlength="255"><?= Security::e($v('seo_description')) ?></textarea>
    </div>
  </details>

  <button class="admin-btn admin-btn--primary admin-btn--block" type="submit" style="margin-top:1rem">
    <?= $isNew ? 'Créer le produit' : 'Enregistrer' ?>
  </button>
</form>

<?php if (!$isNew): ?>

<!-- --------------------------------------------------------- Images -->
<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Photos</h2>
    <span id="img-count" style="font-size:.75rem;color:#6b7280"><?= count($images) ?> photo(s)</span>
  </div>

  <div id="img-dropzone" class="admin-dropzone"
       data-product-id="<?= (int) $product['id'] ?>"
       data-upload-url="<?= Url::admin('products/' . (int) $product['id'] . '/images') ?>"
       data-sort-url="<?= Url::admin('products/' . (int) $product['id'] . '/images/sort') ?>"
       data-primary-url-template="<?= Url::admin('products/' . (int) $product['id'] . '/images/{iid}/primary') ?>"
       data-delete-url-template="<?= Url::admin('products/' . (int) $product['id'] . '/images/{iid}/delete') ?>">
    <input type="file" id="img-input-camera" accept="image/jpeg,image/png,image/webp"
           multiple capture="environment" hidden>
    <input type="file" id="img-input-files" accept="image/jpeg,image/png,image/webp" multiple hidden>

    <svg class="admin-dropzone__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
      <path d="M4 8h3l2-3h6l2 3h3v12H4V8z"/><circle cx="12" cy="13" r="4"/>
    </svg>
    <p class="admin-dropzone__hint"><strong>Glissez vos photos ici</strong></p>
    <p class="admin-dropzone__or">ou choisissez</p>
    <div class="admin-dropzone__btns">
      <button type="button" class="admin-btn admin-btn--primary admin-btn--sm" data-pick="camera">
        📷 Caméra
      </button>
      <button type="button" class="admin-btn admin-btn--dark admin-btn--sm" data-pick="files">
        🖼 Fichiers
      </button>
    </div>
    <small class="admin-dropzone__info">JPEG, PNG ou WebP · 2 Mo max · 8 photos · glisser une vignette pour changer l'ordre</small>
    <div class="admin-dropzone__progress" hidden>
      <div class="admin-dropzone__bar"></div>
    </div>
  </div>

  <div id="img-grid" class="admin-imggrid" style="margin-top:.8rem">
    <?php foreach ($images as $i => $img): $isPrimary = $i === 0; ?>
      <div class="admin-imggrid__item <?= $isPrimary ? 'is-primary' : '' ?>" draggable="true" data-id="<?= (int) $img['id'] ?>">
        <img src="<?= \App\Services\ImageService::thumbUrl((string) $img['file']) ?>" alt="" loading="lazy">
        <?php if ($isPrimary): ?><span class="admin-imggrid__star" title="Image principale">★</span><?php endif; ?>
        <div class="admin-imggrid__actions">
          <?php if (!$isPrimary): ?>
            <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/images/' . (int) $img['id'] . '/primary') ?>" style="margin:0">
              <button type="submit" title="Mettre en principale">★</button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/images/' . (int) $img['id'] . '/delete') ?>" style="margin:0"
                data-confirm="Supprimer cette image ?">
            <button type="submit" title="Supprimer" class="is-danger">✕</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- --------------------------------------------------------- Variantes -->
<div class="admin-card">
  <div class="admin-card__head">
    <h2 class="admin-card__title">Variantes (taille / couleur / stock)</h2>
  </div>

  <?php if ($variants === []): ?>
    <p class="admin-card__sub" style="margin-bottom:.6rem">Aucune variante. Ajoutez des combinaisons pour gérer le stock fin.</p>
  <?php else: ?>
    <?php foreach ($variants as $v): ?>
      <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/variants/' . (int) $v['id']) ?>" class="admin-variant">
        <div class="admin-variant__info">
          <strong><?= Security::e(trim(($v['size'] ?? '') . ' ' . ($v['color'] ?? '')) ?: 'Sans libellé') ?></strong>
          <?php if (!empty($v['sku'])): ?><div class="admin-variant__meta">SKU: <?= Security::e($v['sku']) ?></div><?php endif; ?>
          <?php if ($v['price_override'] !== null): ?><div class="admin-variant__meta">Prix: <?= number_format((int) $v['price_override'], 0, ',', ' ') ?></div><?php endif; ?>
        </div>
        <input type="hidden" name="size" value="<?= Security::e((string) ($v['size'] ?? '')) ?>">
        <input type="hidden" name="color" value="<?= Security::e((string) ($v['color'] ?? '')) ?>">
        <input type="hidden" name="sku" value="<?= Security::e((string) ($v['sku'] ?? '')) ?>">
        <input type="hidden" name="price_override" value="<?= $v['price_override'] !== null ? (int) $v['price_override'] : '' ?>">
        <label style="font-size:.72rem;color:#6b7280">Stock
          <input type="number" name="stock" min="0" value="<?= (int) $v['stock'] ?>" style="width:70px;min-height:36px;padding:.3rem .5rem;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0c0c0e;color:#fff;text-align:center">
        </label>
        <label class="admin-check" style="min-height:auto;font-size:.72rem">
          <input type="checkbox" name="is_active" value="1" <?= !empty($v['is_active']) ? 'checked' : '' ?>> Actif
        </label>
        <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">✓</button>
      </form>
      <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/variants/' . (int) $v['id'] . '/delete') ?>" style="margin:-.4rem 0 .5rem"
            data-confirm="Supprimer cette variante ?">
        <button class="admin-btn admin-btn--danger admin-btn--sm" type="submit">Supprimer</button>
      </form>
    <?php endforeach; ?>
  <?php endif; ?>

  <details style="margin-top:.8rem">
    <summary style="cursor:pointer;font-size:.82rem;color:#9ca3af;font-weight:700">+ Ajouter une variante</summary>
    <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/variants') ?>" style="margin-top:.7rem">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <div class="admin-field">
          <label for="v-size">Taille</label>
          <input id="v-size" name="size" maxlength="20" placeholder="S / M / L / XL">
        </div>
        <div class="admin-field">
          <label for="v-color">Couleur</label>
          <input id="v-color" name="color" maxlength="40" placeholder="Noir / Gris…">
        </div>
        <div class="admin-field">
          <label for="v-sku">SKU</label>
          <input id="v-sku" name="sku" maxlength="60">
        </div>
        <div class="admin-field">
          <label for="v-price">Prix override</label>
          <input id="v-price" name="price_override" type="number" min="0" placeholder="Laisser vide">
        </div>
      </div>
      <div class="admin-field">
        <label for="v-stock">Stock *</label>
        <input id="v-stock" name="stock" type="number" min="0" required value="0">
      </div>
      <button class="admin-btn admin-btn--primary admin-btn--sm" type="submit">Ajouter</button>
    </form>
  </details>
</div>

<!-- --------------------------------------------------------- Suppression -->
<div class="admin-card" style="border-color:rgba(212,0,0,.2)">
  <div class="admin-card__head">
    <h2 class="admin-card__title" style="color:#f87171">Zone dangereuse</h2>
  </div>
  <p class="admin-card__sub" style="margin-bottom:.7rem">La suppression est définitive (produit, images, variantes).</p>
  <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/delete') ?>"
        data-confirm="Supprimer définitivement ce produit ? Cette action est irréversible.">
    <button class="admin-btn admin-btn--danger admin-btn--sm" type="submit">Supprimer le produit</button>
  </form>
</div>

<?php endif; ?>
