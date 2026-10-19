<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;
use App\Helpers\Url;
use App\Repositories\AdminCategoryRepository;
use App\Repositories\AdminProductRepository;
use App\Services\ImageService;

/**
 * CRUD produits admin + variantes + images.
 * Toute mutation est POST + CSRF (global) + journalisée.
 */
final class Products extends BaseAdmin
{
    public function index(): never
    {
        $f = [
            'q' => trim(Request::str('q', '')),
            'status' => Request::str('status', ''),
            'sort' => Request::str('sort', ''),
            'page' => max(1, Request::int('page', 1)),
        ];
        self::render('admin/products/index', AdminProductRepository::list($f) + ['filters' => $f], 'Produits');
    }

    public function create(): never
    {
        self::render('admin/products/form', [
            'product' => null,
            'categories' => AdminCategoryRepository::all(),
        ], 'Nouveau produit');
    }

    public function store(): never
    {
        $data = $this->validate();
        if (AdminProductRepository::slugExists($data['slug'])) {
            self::backError('Ce slug existe déjà.');
        }
        $id = AdminProductRepository::create($data);
        self::log('product.create', $data['name'] . ' (#' . $id . ')');
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Produit créé.');
    }

    public function edit(string $id): never
    {
        $id = (int) $id;
        $product = AdminProductRepository::find($id);
        if ($product === null) {
            Response::abort(404, 'Produit introuvable.');
        }
        self::render('admin/products/form', [
            'product' => $product,
            'categories' => AdminCategoryRepository::all(),
        ], 'Modifier — ' . $product['name']);
    }

    public function update(string $id): never
    {
        $id = (int) $id;
        $product = AdminProductRepository::find($id);
        if ($product === null) {
            Response::abort(404, 'Produit introuvable.');
        }
        $data = $this->validate();
        if (AdminProductRepository::slugExists($data['slug'], $id)) {
            self::backError('Ce slug est déjà utilisé par un autre produit.');
        }
        AdminProductRepository::update($id, $data);
        self::log('product.update', $data['name'] . ' (#' . $id . ')');
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Produit mis à jour.');
    }

    public function destroy(string $id): never
    {
        $id = (int) $id;
        $p = AdminProductRepository::find($id);
        if ($p === null) {
            Response::abort(404);
        }
        AdminProductRepository::delete($id);
        self::log('product.delete', $p['name'] . ' (#' . $id . ')');
        self::redirect(Url::admin('products'), 'Produit supprimé.');
    }

    public function toggleFeatured(string $id): never
    {
        $id = (int) $id;
        if (AdminProductRepository::find($id) === null) {
            Response::abort(404, 'Produit introuvable.');
        }
        AdminProductRepository::toggleFeatured($id);
        self::log('product.featured', '#' . $id);
        // M1 : Referer validé (anti open-redirect)
        Response::redirect(self::safeReturnUrl(Url::admin('products')));
    }

    public function setStatus(string $id): never
    {
        $id = (int) $id;
        $status = Request::post('status', '');
        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            self::backError('Statut invalide.');
        }
        $p = AdminProductRepository::find($id);
        if ($p === null) {
            Response::abort(404);
        }
        AdminProductRepository::setStatus($id, $status);
        self::log('product.status', $p['name'] . ' → ' . $status);
        // M1 : Referer validé (anti open-redirect)
        Response::redirect(self::safeReturnUrl(Url::admin('products')));
    }

    // ---------------------------------------------------------- Variantes

    public function variantStore(string $id): never
    {
        $id = (int) $id;
        if (AdminProductRepository::find($id) === null) {
            Response::abort(404);
        }
        $stock = Request::int('stock', -1);
        if ($stock < 0) {
            self::backError('Stock invalide.');
        }
        try {
            $variantId = AdminProductRepository::createVariant($id, [
                'size' => trim(Request::str('size', '')),
                'color' => trim(Request::str('color', '')),
                'sku' => trim(Request::str('sku', '')),
                'price_override' => Request::str('price_override', ''),
                'stock' => $stock,
            ]);
        } catch (\PDOException) {
            self::backError('Variante déjà existante (même taille/couleur) pour ce produit.');
        }
        self::log('variant.create', 'produit #' . $id . ' variante #' . $variantId);
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Variante ajoutée.');
    }

    public function variantUpdate(string $id, string $variantId): never
    {
        $id = (int) $id;
        $variantId = (int) $variantId;
        if (!$this->variantBelongsTo($id, $variantId)) {
            Response::abort(404, 'Variante introuvable pour ce produit.');
        }
        $stock = Request::int('stock', 0);
        try {
            AdminProductRepository::updateVariant($variantId, [
                'size' => trim(Request::str('size', '')),
                'color' => trim(Request::str('color', '')),
                'sku' => trim(Request::str('sku', '')),
                'price_override' => Request::str('price_override', ''),
                'stock' => max(0, $stock),
                'is_active' => Request::post('is_active') !== null,
            ]);
        } catch (\PDOException) {
            self::backError('Variante déjà existante (même taille/couleur) pour ce produit.');
        }
        self::log('variant.update', '#' . $variantId);
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Variante mise à jour.');
    }

    public function variantDestroy(string $id, string $variantId): never
    {
        $id = (int) $id;
        if (!$this->variantBelongsTo($id, (int) $variantId)) {
            Response::abort(404, 'Variante introuvable pour ce produit.');
        }
        AdminProductRepository::deleteVariant((int) $variantId);
        self::log('variant.delete', '#' . $variantId);
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Variante supprimée.');
    }

    /** L9 : la variante doit appartenir au produit de l'URL (anti IDOR). */
    private function variantBelongsTo(int $productId, int $variantId): bool
    {
        $n = (int) Database::value(
            'SELECT COUNT(*) FROM product_variants WHERE id = ? AND product_id = ?',
            [$variantId, $productId]
        );
        return $n > 0;
    }

    // ------------------------------------------------------------- Images

    /**
     * Upload multi-fichiers (Sprint B) : drag & drop ou capture caméra.
     * Réponse JSON si requête AJAX, sinon redirection classique.
     */
    public function imageUpload(string $id): never
    {
        $id = (int) $id;
        if (AdminProductRepository::find($id) === null) {
            Response::abort(404, 'Produit introuvable.');
        }
        $files = self::collectFiles();
        if ($files === []) {
            $msg = 'Aucune image reçue. Formats acceptés : JPEG, PNG, WebP (max 2 Mo).';
            if (Request::isAjax()) {
                Response::error($msg, 400);
            }
            self::backError($msg);
        }

        $result = ImageService::saveProductImages($files, 8);
        foreach ($result['saved'] as $name) {
            AdminProductRepository::addImage($id, $name);
            self::log('image.upload', $name . ' → produit #' . $id);
        }
        $count = count($result['saved']);
        $message = $count > 1 ? $count . ' images ajoutées.' : ($count === 1 ? 'Image ajoutée.' : '');
        if ($result['errors'] !== []) {
            $message = ($message !== '' ? $message . ' ' : '') . implode(' ', $result['errors']);
        }
        if ($count === 0) {
            if (Request::isAjax()) {
                Response::error(implode(' ', $result['errors']) ?: 'Échec de l\'upload.', 400);
            }
            self::backError(implode(' ', $result['errors']) ?: 'Échec de l\'upload.');
        }

        if (Request::isAjax()) {
            $rows = [];
            foreach (AdminProductRepository::images($id) as $img) {
                $rows[] = [
                    'id' => (int) $img['id'],
                    'file' => (string) $img['file'],
                    'url' => ImageService::url((string) $img['file']),
                    'thumb' => ImageService::thumbUrl((string) $img['file']),
                    'primary' => (int) $img['sort_order'] < 0,
                ];
            }
            Response::json(['images' => $rows], 200, $message ?: 'Image ajoutée.');
        }
        self::redirect(Url::admin('products/' . $id . '/edit'), $message ?: 'Image ajoutée.');
    }

    /** Réordonnancement drag & drop (JSON : {"ids":[3,1,2]}). */
    public function imageSort(string $id): never
    {
        $id = (int) $id;
        if (AdminProductRepository::find($id) === null) {
            Response::abort(404, 'Produit introuvable.');
        }
        $ids = Request::json()['ids'] ?? null;
        if (!is_array($ids) || $ids === []) {
            Response::error('Ordre d\'images invalide.', 400);
        }
        $ids = array_values(array_map('intval', $ids));
        // Unicité + appartenance au produit (anti IDOR) + complétude
        if (count($ids) !== count(array_unique($ids))) {
            Response::error('Ordre d\'images invalide (doublon).', 400);
        }
        $owned = array_map('intval', array_column(AdminProductRepository::images($id), 'id'));
        if (count($ids) !== count($owned)) {
            Response::error('L\'ordre doit contenir toutes les images du produit.', 400);
        }
        foreach ($ids as $imgId) {
            if (!in_array($imgId, $owned, true)) {
                Response::error('Image hors de ce produit.', 400);
            }
        }
        AdminProductRepository::reorderImages($id, $ids);
        self::log('image.sort', 'produit #' . $id . ' : ' . implode(',', $ids));
        Response::json(null, 200, 'Ordre mis à jour.');
    }

    public function imageDestroy(string $id, string $imageId): never
    {
        $id = (int) $id;
        $imageId = (int) $imageId;
        // M9 : l'image doit appartenir au produit de l'URL (anti IDOR)
        if (!ImageService::purge($id, $imageId)) {
            $msg = 'Image introuvable pour ce produit.';
            if (Request::isAjax()) {
                Response::error($msg, 404);
            }
            self::backError($msg);
        }
        self::log('image.delete', 'image #' . $imageId . ' (produit #' . $id . ')');
        if (Request::isAjax()) {
            Response::json(null, 200, 'Image supprimée.');
        }
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Image supprimée.');
    }

    public function imagePrimary(string $id, string $imageId): never
    {
        $id = (int) $id;
        $imageId = (int) $imageId;
        // M9 : l'image doit appartenir au produit de l'URL (anti IDOR)
        $found = false;
        foreach (AdminProductRepository::images($id) as $img) {
            if ((int) $img['id'] === $imageId) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $msg = 'Image introuvable pour ce produit.';
            if (Request::isAjax()) {
                Response::error($msg, 404);
            }
            self::backError($msg);
        }
        AdminProductRepository::setPrimaryImage($id, $imageId);
        self::log('image.primary', 'image #' . $imageId);
        if (Request::isAjax()) {
            Response::json(null, 200, 'Image principale définie.');
        }
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Image principale définie.');
    }

    /**
     * Normalise $_FILES : champ `images[]` (multi) ou `image` (unique).
     *
     * @return list<array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    private static function collectFiles(): array
    {
        $out = [];
        foreach (['images', 'image'] as $key) {
            if (empty($_FILES[$key])) {
                continue;
            }
            $f = $_FILES[$key];
            if (is_array($f['name'] ?? null)) {
                foreach ($f['name'] as $i => $name) {
                    $err = (int) ($f['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                    if ($err === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $out[] = [
                        'name' => (string) $name,
                        'type' => (string) ($f['type'][$i] ?? ''),
                        'tmp_name' => (string) ($f['tmp_name'][$i] ?? ''),
                        'error' => $err,
                        'size' => (int) ($f['size'][$i] ?? 0),
                    ];
                }
            } elseif ((int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $out[] = [
                    'name' => (string) ($f['name'] ?? ''),
                    'type' => (string) ($f['type'] ?? ''),
                    'tmp_name' => (string) ($f['tmp_name'] ?? ''),
                    'error' => (int) $f['error'],
                    'size' => (int) ($f['size'] ?? 0),
                ];
            }
        }
        return $out;
    }

    // ------------------------------------------------------------ Helpers

    /** @return array{category_id:int,name:string,slug:string,description:string,price:int,promo_price:?int,status:string,is_featured:bool,seo_title:string,seo_description:string} */
    private function validate(): array
    {
        $name = trim(Request::post('name', ''));
        if ($name === '' || mb_strlen($name) > 160) {
            self::backError('Nom obligatoire (160 caractères max).');
        }
        $slug = trim(Request::post('slug', ''));
        if ($slug === '') {
            $slug = Security::slugify($name);
        } else {
            $slug = Security::slugify($slug);
        }
        if ($slug === '') {
            self::backError('Slug invalide.');
        }
        $price = Request::int('price', 0);
        if ($price <= 0) {
            self::backError('Prix obligatoire (> 0).');
        }
        $promo = Request::str('promo_price', '');
        $promoVal = ($promo !== '' && (int) $promo > 0) ? (int) $promo : null;
        if ($promoVal !== null && $promoVal >= $price) {
            self::backError('Le prix promo doit être inférieur au prix normal.');
        }
        $status = Request::post('status', 'draft');
        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            $status = 'draft';
        }

        return [
            'category_id' => Request::int('category_id', 0),
            'name' => $name,
            'slug' => $slug,
            'description' => trim(Request::post('description', '')),
            'price' => $price,
            'promo_price' => $promoVal,
            'status' => $status,
            'is_featured' => Request::post('is_featured') !== null,
            'seo_title' => trim(Request::post('seo_title', '')),
            'seo_description' => trim(Request::post('seo_description', '')),
        ];
    }
}
