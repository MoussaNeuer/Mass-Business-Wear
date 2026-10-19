<?php
/**
 * Catalogue : recherche, tri, filtres (bottom sheet), chips, pagination.
 *
 * @var ?array $category
 * @var array $categories
 * @var array $filters  q,cat,min,max,size,color,sort,page
 * @var array $result   items,total,pages,page
 * @var array $facets   sizes[],colors[]
 * @var array $chips    label,drop
 * @var array $sorts    code=>libellé
 * @var int $activeFiltersCount
 * @var array $baseQuery
 */
use App\Helpers\Security;
use App\Helpers\Url;

$items = $result['items'];
$page = $result['page'];
$pages = $result['pages'];
$total = $result['total'];
$currency = $currency ?? 'FCFA';

/** URL de pagination/filtre conservant l'état courant. */
$buildUrl = static function (array $overrides) use ($filters, $category): string {
    $q = array_merge(array_filter([
        'q' => $filters['q'],
        'min' => $filters['min'] ?: null,
        'max' => $filters['max'] ?: null,
        'size' => $filters['size'],
        'color' => $filters['color'],
        'sort' => $filters['sort'] !== 'recent' ? $filters['sort'] : null,
    ]), $overrides);
    $q = array_filter($q, static fn ($v) => $v !== null && $v !== '' && $v !== '0');
    $base = $category !== null ? 'category/' . $category['slug'] : 'shop';
    return Url::to($base . ($q !== [] ? '?' . http_build_query($q) : ''));
};

// URL "load more" (page suivante) pour l'API
$apiQuery = array_filter([
    'q' => $filters['q'],
    'cat' => $category['slug'] ?? '',
    'min' => $filters['min'] ?: null,
    'max' => $filters['max'] ?: null,
    'size' => $filters['size'],
    'color' => $filters['color'],
    'sort' => $filters['sort'],
    'page' => $page < $pages ? $page + 1 : null,
]);
$loadMoreUrl = $page < $pages
    ? \App\Helpers\BaseUrl::url('api/products?' . http_build_query($apiQuery))
    : null;
?>
<header class="mt-3 mb-3">
  <?php if ($category !== null): ?>
    <h1 style="font-size:1.5rem;font-weight:800;margin:0"><?= Security::e($category['name']) ?></h1>
    <?php if (!empty($category['description'])): ?>
      <p class="text-muted mb-2" style="font-size:.9rem"><?= Security::e($category['description']) ?></p>
    <?php endif; ?>
  <?php else: ?>
    <h1 style="font-size:1.5rem;font-weight:800;margin:0">
      <?= $filters['q'] !== '' ? 'Résultats pour « ' . Security::e($filters['q']) . ' »' : 'Boutique' ?>
    </h1>
  <?php endif; ?>
  <p class="text-muted mb-0" style="font-size:.85rem"><?= $total ?> produit<?= $total > 1 ? 's' : '' ?></p>
</header>

<div class="catalog-toolbar">
  <form class="searchbar" role="search" method="get"
        action="<?= Url::to($category !== null ? 'category/' . $category['slug'] : 'shop') ?>"
        data-quick-search>
    <input type="search" name="q" value="<?= Security::e($filters['q']) ?>"
           placeholder="Rechercher..." aria-label="Rechercher dans le catalogue"
           autocapitalize="off" enterkeyhint="search">
    <button type="submit" aria-label="Rechercher">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    </button>
    <div class="search-results" hidden></div>
  </form>

  <button type="button" class="filter-btn" data-sheet-open aria-haspopup="dialog">
    Filtres
    <?php if ($activeFiltersCount > 0): ?><span class="count"><?= $activeFiltersCount ?></span><?php endif; ?>
  </button>

  <label class="sr-only" for="sort-select">Trier par</label>
  <select id="sort-select" onchange="if(this.value){var u=new URL(window.location.href);u.searchParams.set('sort',this.value);u.searchParams.delete('page');window.location.href=u.toString()}">
    <?php foreach ($sorts as $code => $label): ?>
      <option value="<?= Security::e($code) ?>" <?= $filters['sort'] === $code ? 'selected' : '' ?>><?= Security::e($label) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<?php if ($chips !== []): ?>
<div class="chips mb-3">
  <?php foreach ($chips as $chip): ?>
    <span class="chip">
      <?= Security::e($chip['label']) ?>
      <a href="<?= $buildUrl([$chip['drop'] => null]) ?>" aria-label="Retirer ce filtre">×</a>
    </span>
  <?php endforeach; ?>
  <a href="<?= $buildUrl(['q' => null, 'min' => null, 'max' => null, 'size' => null, 'color' => null]) ?>"
     style="font-size:.8rem;color:var(--text-muted)">Tout effacer</a>
</div>
<?php endif; ?>

<div class="grid-products" data-product-grid>
  <?php if ($items === []): ?>
    <div class="empty-state">
      <div class="icon" aria-hidden="true">🔍</div>
      <h3>Aucun produit trouvé</h3>
      <p>
        <?php if ($filters['q'] !== ''): ?>
          Aucun résultat pour « <?= Security::e($filters['q']) ?> ».
        <?php else: ?>
          Essayez d'élargir vos filtres ou de changer de catégorie.
        <?php endif; ?>
      </p>
      <a class="btn-mbw btn-primary" href="<?= Url::to('shop') ?>">Voir tout le catalogue</a>
    </div>
  <?php else: ?>
    <?php foreach ($items as $p): ?>
      <?= \App\Helpers\View::partial('partials/product-card', ['p' => $p, 'currency' => $currency]) ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if ($loadMoreUrl !== null): ?>
<div class="text-center mt-4">
  <button type="button" class="btn-mbw btn-dark" data-load-more="<?= Security::e($loadMoreUrl) ?>">
    Charger plus
  </button>
</div>
<?php endif; ?>

<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Pagination">
  <?php if ($page > 1): ?>
    <a href="<?= $buildUrl(['page' => $page - 1]) ?>" rel="prev">← Préc.</a>
  <?php else: ?>
    <span class="is-disabled">← Préc.</span>
  <?php endif; ?>

  <?php
  // Fenêtre : 1 ... p-1 p p+1 ... N
  $range = range(max(1, $page - 2), min($pages, $page + 2));
  $shown = 0;
  foreach ($range as $i) {
      if ($i > 1 && $i > ($range[0] ?? 1) + 1) {
          echo '<span class="is-disabled">…</span>';
      }
      if ($i === $page) {
          echo '<span class="is-current" aria-current="page">' . $i . '</span>';
      } else {
          echo '<a href="' . Security::e($buildUrl(['page' => $i])) . '">' . $i . '</a>';
      }
      $shown++;
  }
  if ($pages > ($range[count($range) - 1] ?? $page)) {
      if (($range[count($range) - 1] ?? $page) < $pages - 1) {
          echo '<span class="is-disabled">…</span>';
      }
      echo '<a href="' . Security::e($buildUrl(['page' => $pages])) . '">' . $pages . '</a>';
  }
  ?>

  <?php if ($page < $pages): ?>
    <a href="<?= $buildUrl(['page' => $page + 1]) ?>" rel="next">Suiv. →</a>
  <?php else: ?>
    <span class="is-disabled">Suiv. →</span>
  <?php endif; ?>
</nav>
<?php endif; ?>

<!-- Bottom sheet : filtres -->
<div class="bottom-sheet" id="filter-sheet" role="dialog" aria-modal="true" aria-labelledby="filter-title" hidden>
  <div class="bottom-sheet__handle" aria-hidden="true"></div>
  <div class="bottom-sheet__head">
    <h3 id="filter-title">Filtres</h3>
    <button type="button" class="icon-btn" data-sheet-close aria-label="Fermer">✕</button>
  </div>
  <form id="filter-form" method="get"
        action="<?= Url::to($category !== null ? 'category/' . $category['slug'] : 'shop') ?>">
    <?php if ($filters['q'] !== ''): ?>
      <input type="hidden" name="q" value="<?= Security::e($filters['q']) ?>">
    <?php endif; ?>
    <?php if ($filters['sort'] !== 'recent'): ?>
      <input type="hidden" name="sort" value="<?= Security::e($filters['sort']) ?>">
    <?php endif; ?>

    <div class="bottom-sheet__body">
      <?php if ($facets['sizes'] !== []): ?>
      <div class="sheet-group">
        <h4>Taille</h4>
        <div class="pills">
          <?php foreach ($facets['sizes'] as $size): ?>
            <label class="check-pill">
              <input type="radio" name="size" value="<?= Security::e($size) ?>" <?= $filters['size'] === $size ? 'checked' : '' ?>>
              <?= Security::e($size) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($facets['colors'] !== []): ?>
      <div class="sheet-group">
        <h4>Couleur</h4>
        <div class="pills">
          <?php foreach ($facets['colors'] as $color): ?>
            <label class="check-pill">
              <input type="radio" name="color" value="<?= Security::e($color) ?>" <?= $filters['color'] === $color ? 'checked' : '' ?>>
              <?= Security::e($color) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="sheet-group">
        <h4>Prix (<?= Security::e($currency) ?>)</h4>
        <div class="price-row">
          <input class="field-input" type="number" name="min" min="0" step="500"
                 placeholder="Min" value="<?= $filters['min'] ?: '' ?>" inputmode="numeric"
                 style="min-height:48px;padding:.6rem .8rem;border-radius:8px;border:1px solid var(--border);background:var(--surface-2);color:var(--text);width:100%">
          <input type="number" name="max" min="0" step="500"
                 placeholder="Max" value="<?= $filters['max'] ?: '' ?>" inputmode="numeric"
                 style="min-height:48px;padding:.6rem .8rem;border-radius:8px;border:1px solid var(--border);background:var(--surface-2);color:var(--text);width:100%">
        </div>
      </div>
    </div>

    <div class="bottom-sheet__foot">
      <button type="button" class="btn-mbw btn-dark" data-filter-reset>Réinitialiser</button>
      <button type="submit" class="btn-mbw btn-primary">Voir les résultats</button>
    </div>
  </form>
</div>
