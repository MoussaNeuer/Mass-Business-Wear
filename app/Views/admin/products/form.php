<?php
/**
 * Formulaire produit (create / edit) + variantes + images.
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
    <input id="p-name" name="name" required maxlength="160" value="<?= Security::e((string) ($product['name'] ?? '')) ?>" placeholder="Ex. Sweat Capuche MBW">
  </div>

  <div class="admin-field">
    <label for="p-slug">Slug (URL)</label>
    <input id="p-slug" name="slug" maxlength="180" value="<?= Security::e((string) ($product['slug'] ?? '')) ?>" placeholder="auto si vide">
    <small>Caractères minuscules, tirets. Généré depuis le nom si vide.</small>
  </div>

  <div class="admin-field">
    <label for="p-cat">Catégorie</label>
    <select id="p-cat" name="category_id">
      <option value="0">— Aucune —</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
          <?= Security::e((string) $c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="admin-field">
    <label for="p-desc">Description</label>
    <textarea id="p-desc" name="description" rows="4" placeholder="Matière, coupe, entretien…"><?= Security::e((string) ($product['description'] ?? '')) ?></textarea>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
    <div class="admin-field">
      <label for="p-price">Prix (FCFA) *</label>
      <input id="p-price" name="price" type="number" min="1" required value="<?= (int) ($product['price'] ?? 0) ?: '' ?>">
    </div>
    <div class="admin-field">
      <label for="p-promo">Prix promo</label>
      <input id="p-promo" name="promo_price" type="number" min="0" value="<?= $product['promo_price'] !== null ? (int) $product['promo_price'] : '' ?>" placeholder=" Vide = pas de promo">
    </div>
  </div>

  <div class="admin-field">
    <label for="p-status">Statut</label>
    <select id="p-status" name="status">
      <?php foreach (['draft' => 'Brouillon', 'active' => 'Actif (visible)', 'archived' => 'Archivé'] as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($product['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <label class="admin-check">
    <input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>>
    Mis en avant sur l'accueil
  </label>

  <details style="margin-top:.8rem">
    <summary style="cursor:pointer;font-size:.82rem;color:#9ca3af;font-weight:700">SEO (optionnel)</summary>
    <div class="admin-field" style="margin-top:.6rem">
      <label for="p-seo-t">Titre SEO</label>
      <input id="p-seo-t" name="seo_title" maxlength="160" value="<?= Security::e((string) ($product['seo_title'] ?? '')) ?>">
    </div>
    <div class="admin-field">
      <label for="p-seo-d">Description SEO</label>
      <textarea id="p-seo-d" name="seo_description" rows="2" maxlength="255"><?= Security::e((string) ($product['seo_description'] ?? '')) ?></textarea>
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
    <h2 class="admin-card__title">Images</h2>
    <span style="font-size:.75rem;color:#6b7280"><?= count($images) ?> image(s)</span>
  </div>

  <?php if ($images !== []): ?>
    <div class="admin-imggrid">
      <?php $minSort = $images[0]['sort_order'] ?? 0; foreach ($images as $img): $isPrimary = (int) $img['sort_order'] <= (int) $minSort; ?>
        <div class="admin-imggrid__item <?= $isPrimary ? 'is-primary' : '' ?>">
          <img src="<?= Url::upload('products/' . Security::e($img['file'])) ?>" alt="" loading="lazy">
          <div class="admin-imggrid__actions">
            <?php if (!$isPrimary): ?>
              <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/images/' . (int) $img['id'] . '/primary') ?>" style="margin:0">
                <button type="submit" title="Principale">★</button>
              </form>
            <?php else: ?>
              <span style="display:grid;place-items:center;color:#fbbf24;font-size:.65rem;font-weight:800">★</span>
            <?php endif; ?>
            <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/images/' . (int) $img['id'] . '/delete') ?>" style="margin:0"
                  data-confirm="Supprimer cette image ?">
              <button type="submit" title="Supprimer" style="color:#f87171">✕</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="admin-card__sub" style="margin-bottom:.6rem">Aucune image. Ajoutez des photos (JPEG/PNG/WebP, max 2 Mo).</p>
  <?php endif; ?>

  <form method="post" action="<?= Url::admin('products/' . (int) $product['id'] . '/images') ?>" enctype="multipart/form-data" class="mt-3">
    <div class="admin-field">
      <label for="p-img">Ajouter une image</label>
      <input id="p-img" type="file" name="image" accept="image/jpeg,image/png,image/webp" required data-preview="#img-preview">
      <img id="img-preview" src="" alt="" style="display:none;max-width:120px;border-radius:10px;margin-top:.5rem">
    </div>
    <button class="admin-btn admin-btn--dark admin-btn--sm" type="submit">Téléverser</button>
  </form>
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
