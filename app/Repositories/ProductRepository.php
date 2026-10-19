<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/**
 * Requêtes catalogue. Prix effectif = COALESCE(promo_price, price).
 * Toutes les listes publiques sont restreintes aux produits 'active'.
 */
final class ProductRepository
{
    public const PER_PAGE = 12;

    public const SORTS = [
        'recent' => 'p.created_at DESC, p.id DESC',
        'price_asc' => 'eff_price ASC, p.id ASC',
        'price_desc' => 'eff_price DESC, p.id DESC',
        'popular' => 'p.sold_count DESC, p.created_at DESC',
        'name' => 'p.name ASC',
    ];

    private const SELECT_CARD = "
        SELECT p.id, p.name, p.slug, p.price, p.promo_price,
               COALESCE(p.promo_price, p.price) AS eff_price,
               p.is_featured, p.sold_count, p.created_at,
               c.name AS category_name, c.slug AS category_slug,
               (SELECT file FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order ASC, i.id ASC LIMIT 1) AS image,
               (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS stock
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'active'
    ";

    /**
     * Catalogue filtré + paginé.
     *
     * @param array{q?:string,cat?:string,min?:int,max?:int,size?:string,color?:string,sort?:string,page?:int} $f
     * @return array{items:array,total:int,pages:int,page:int}
     */
    public static function search(array $f): array
    {
        $where = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = 'AND (p.name LIKE ? OR p.description LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $f['q']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($f['cat'])) {
            $where[] = 'AND c.slug = ?';
            $params[] = (string) $f['cat'];
        }

        if (!empty($f['min'])) {
            $where[] = 'AND COALESCE(p.promo_price, p.price) >= ?';
            $params[] = (int) $f['min'];
        }

        if (!empty($f['max'])) {
            $where[] = 'AND COALESCE(p.promo_price, p.price) <= ?';
            $params[] = (int) $f['max'];
        }

        if (!empty($f['size'])) {
            $where[] = 'AND EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 AND v.size = ?)';
            $params[] = (string) $f['size'];
        }

        if (!empty($f['color'])) {
            $where[] = 'AND EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 AND v.color = ?)';
            $params[] = (string) $f['color'];
        }

        $whereSql = $where === [] ? '' : ' ' . implode(' ', $where);

        $total = (int) (Database::value(
            'SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = \'active\'' . $whereSql,
            $params
        ) ?? 0);

        $page = max(1, (int) ($f['page'] ?? 1));
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $orderBy = self::SORTS[$f['sort'] ?? ''] ?? self::SORTS['recent'];

        $items = Database::all(
            self::SELECT_CARD . $whereSql . " ORDER BY {$orderBy} LIMIT {$offset}, " . self::PER_PAGE,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Section d'accueil (nouveautés, best-sellers...). */
    public static function homeSection(string $type, int $limit = 8): array
    {
        $orderBy = match ($type) {
            'featured' => 'p.is_featured DESC, p.sold_count DESC, p.created_at DESC',
            'best' => 'p.sold_count DESC, p.created_at DESC',
            'promo' => 'p.promo_price IS NULL, p.created_at DESC',
            default => 'p.created_at DESC, p.id DESC',
        };

        $extra = '';
        if ($type === 'promo') {
            $extra = ' AND p.promo_price IS NOT NULL';
        } elseif ($type === 'featured') {
            $extra = ' AND p.is_featured = 1';
        }

        $limit = max(1, min(16, $limit));

        return Database::all(
            self::SELECT_CARD . $extra . " ORDER BY {$orderBy} LIMIT {$limit}",
            []
        );
    }

    /** Fiche produit complète (sans variantes) pour une page publique. */
    public static function findActiveBySlug(string $slug): ?array
    {
        return Database::one(
            "SELECT p.id, p.category_id, p.name, p.slug, p.description,
                    p.price, p.promo_price, COALESCE(p.promo_price, p.price) AS eff_price,
                    p.is_featured, p.sold_count, p.created_at,
                    c.name AS category_name, c.slug AS category_slug,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.status = 'active'
             LIMIT 1",
            [$slug]
        );
    }

    /** @return array<int,array{file:string,sort_order:int}> */
    public static function images(int $productId): array
    {
        return Database::all(
            'SELECT file, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
            [$productId]
        );
    }

    /** Variantes actives groupées pour l'affichage taille/couleur. */
    public static function variants(int $productId): array
    {
        return Database::all(
            'SELECT id, size, color, sku, price_override, stock
             FROM product_variants
             WHERE product_id = ? AND is_active = 1
             ORDER BY size IS NULL, size, color IS NULL, color',
            [$productId]
        );
    }

    /** Produits similaires : même catégorie, sinon les plus vendus. */
    public static function similar(int $productId, ?int $categoryId, int $limit = 4): array
    {
        $limit = max(1, min(8, $limit));

        if ($categoryId !== null) {
            $items = Database::all(
                self::SELECT_CARD . ' AND p.category_id = ? AND p.id != ? ORDER BY p.sold_count DESC, p.created_at DESC LIMIT ' . $limit,
                [$categoryId, $productId]
            );
            if ($items !== []) {
                return $items;
            }
        }

        return Database::all(
            self::SELECT_CARD . ' AND p.id != ? ORDER BY p.sold_count DESC, p.created_at DESC LIMIT ' . $limit,
            [$productId]
        );
    }

    /** Recherche rapide (header) : noms + prix, limite courte. */
    public static function quickSearch(string $q, int $limit = 6): array
    {
        $limit = max(1, min(10, $limit));
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';

        return Database::all(
            self::SELECT_CARD . ' AND (p.name LIKE ? OR p.description LIKE ?)
             ORDER BY p.sold_count DESC, p.created_at DESC LIMIT ' . $limit,
            [$like, $like]
        );
    }

    /** Facettes pour les filtres (tailles/couleurs réellement présentes). */
    public static function facets(?string $catSlug = null): array
    {
        $where = "p.status = 'active'";
        $params = [];
        if ($catSlug !== null && $catSlug !== '') {
            $where .= ' AND c.slug = ?';
            $params[] = $catSlug;
        }

        $sizes = Database::all(
            "SELECT DISTINCT v.size AS value FROM product_variants v
             JOIN products p ON p.id = v.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$where} AND v.is_active = 1 AND v.size IS NOT NULL AND v.size <> ''
             ORDER BY v.size",
            $params
        );

        $colors = Database::all(
            "SELECT DISTINCT v.color AS value FROM product_variants v
             JOIN products p ON p.id = v.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$where} AND v.is_active = 1 AND v.color IS NOT NULL AND v.color <> ''
             ORDER BY v.color",
            $params
        );

        return [
            'sizes' => array_column($sizes, 'value'),
            'colors' => array_column($colors, 'value'),
        ];
    }
}
