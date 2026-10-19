<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Security;
use App\Helpers\Url;
use App\Repositories\AdminCategoryRepository;
use App\Repositories\AdminProductRepository;
use RuntimeException;

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
        AdminProductRepository::toggleFeatured($id);
        self::log('product.featured', '#' . $id);
        $ref = Request::header('Referer');
        Response::redirect(is_string($ref) ? $ref : Url::admin('products'));
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
        $ref = Request::header('Referer');
        Response::redirect(is_string($ref) ? $ref : Url::admin('products'));
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
        AdminProductRepository::deleteVariant((int) $variantId);
        self::log('variant.delete', '#' . $variantId);
        self::redirect(Url::admin('products/' . (int) $id . '/edit'), 'Variante supprimée.');
    }

    // ------------------------------------------------------------- Images

    public function imageUpload(string $id): never
    {
        $id = (int) $id;
        if (AdminProductRepository::find($id) === null) {
            Response::abort(404);
        }
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            self::backError('Aucune image reçue ou erreur d\'upload.');
        }
        $file = $this->handleUpload($_FILES['image']);
        AdminProductRepository::addImage($id, $file);
        self::log('image.upload', $file . ' → produit #' . $id);
        self::redirect(Url::admin('products/' . $id . '/edit'), 'Image ajoutée.');
    }

    public function imageDestroy(string $id, string $imageId): never
    {
        $images = AdminProductRepository::images((int) $id);
        foreach ($images as $img) {
            if ((int) $img['id'] === (int) $imageId) {
                $path = dirname(__DIR__, 3) . '/public/uploads/products/' . $img['file'];
                if (is_file($path)) {
                    @unlink($path);
                }
                AdminProductRepository::deleteImage((int) $imageId);
                self::log('image.delete', $img['file']);
                break;
            }
        }
        self::redirect(Url::admin('products/' . (int) $id . '/edit'), 'Image supprimée.');
    }

    public function imagePrimary(string $id, string $imageId): never
    {
        AdminProductRepository::setPrimaryImage((int) $id, (int) $imageId);
        self::log('image.primary', 'image #' . $imageId);
        self::redirect(Url::admin('products/' . (int) $id . '/edit'), 'Image principale définie.');
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

    /** Upload image produit : MIME réel, 2 Mo max, JPEG/PNG/WebP, nom aléatoire. */
    private function handleUpload(array $file): string
    {
        if ((int) $file['size'] > 2 * 1024 * 1024) {
            self::backError('Image trop lourde (max 2 Mo).');
        }
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            self::backError('Fichier image invalide.');
        }
        $mime = $info['mime'] ?? '';
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
        if ($ext === null) {
            self::backError('Format non autorisé (JPEG, PNG ou WebP).');
        }

        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $dest = dirname(__DIR__, 3) . '/public/uploads/products/' . $name;
        $dir = dirname($dest);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            self::backError('Dossier d\'upload inaccessible.');
        }
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            // repli pour tests CLI
            if (!@rename($file['tmp_name'], $dest) && !@copy($file['tmp_name'], $dest)) {
                self::backError('Échec de l\'enregistrement de l\'image.');
            }
        }
        @chmod($dest, 0644);
        return $name;
    }
}
