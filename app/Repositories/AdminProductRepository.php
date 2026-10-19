<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/**
 * Requêtes admin produits / variantes / images.
 * Liste sans filtre de statut (admin voit tout).
 */
final class AdminProductRepository
{
    public const PER_PAGE = 20;

    /**
     * @param array{q?:string,status?:string,sort?:string,page?:int} $f
     * @return array{items:array,total:int,pages:int,page:int}
     */
    public static function list(array $f): array
    {
        $where = [];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = 'AND (p.name LIKE ? OR p.slug LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $f['q']) . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($f['status']) && in_array($f['status'], ['draft', 'active', 'archived'], true)) {
            $where[] = 'AND p.status = ?';
            $params[] = $f['status'];
        }
        $whereSql = $where === [] ? '' : ' ' . implode(' ', $where);

        $total = (int) (Database::value('SELECT COUNT(*) FROM products p WHERE 1=1' . $whereSql, $params) ?? 0);
        $page = max(1, (int) ($f['page'] ?? 1));
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $orderBy = match ($f['sort'] ?? '') {
            'name' => 'p.name ASC',
            'stock' => 'stock ASC, p.name ASC',
            'sold' => 'p.sold_count DESC, p.name ASC',
            default => 'p.updated_at DESC, p.id DESC',
        };

        $items = Database::all(
            "SELECT p.id, p.name, p.slug, p.price, p.promo_price, p.status, p.is_featured,
                    p.sold_count, p.created_at, p.updated_at,
                    c.name AS category_name,
                    (SELECT file FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE 1=1{$whereSql}
             ORDER BY {$orderBy}
             LIMIT {$offset}, " . self::PER_PAGE,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            "SELECT p.*, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? LIMIT 1",
            [$id]
        );
    }

    /** @return array{items:array,pages:int,page:int,total:int} */
    public static function paginate(int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $items = Database::all(
            'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.updated_at DESC LIMIT ' . $offset . ', ' . $perPage
        );
        $total = (int) Database::value('SELECT COUNT(*) FROM products');
        return ['items' => $items, 'page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public static function create(array $d): int
    {
        Database::query(
            'INSERT INTO products (category_id, name, slug, description, price, promo_price, status, is_featured, seo_title, seo_description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $d['category_id'] ?: null,
                $d['name'],
                $d['slug'],
                $d['description'] ?: null,
                $d['price'],
                $d['promo_price'] ?: null,
                $d['status'],
                $d['is_featured'] ? 1 : 0,
                $d['seo_title'] ?: null,
                $d['seo_description'] ?: null,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, promo_price=?, status=?, is_featured=?, seo_title=?, seo_description=? WHERE id=?',
            [
                $d['category_id'] ?: null,
                $d['name'],
                $d['slug'],
                $d['description'] ?: null,
                $d['price'],
                $d['promo_price'] ?: null,
                $d['status'],
                $d['is_featured'] ? 1 : 0,
                $d['seo_title'] ?: null,
                $d['seo_description'] ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM products WHERE id = ?', [$id]);
    }

    public static function toggleFeatured(int $id): void
    {
        Database::query('UPDATE products SET is_featured = 1 - is_featured WHERE id = ?', [$id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE products SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM products WHERE slug = ?';
        $params = [$slug];
        if ($exceptId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $exceptId;
        }
        return (int) Database::value($sql, $params) > 0;
    }

    // ------------------------------------------------------------ Variantes

    public static function variants(int $productId): array
    {
        return Database::all(
            'SELECT * FROM product_variants WHERE product_id = ? ORDER BY size IS NULL, size, color IS NULL, color',
            [$productId]
        );
    }

    public static function createVariant(int $productId, array $d): int
    {
        Database::query(
            'INSERT INTO product_variants (product_id, size, color, sku, price_override, stock, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)',
            [
                $productId,
                $d['size'] ?: null,
                $d['color'] ?: null,
                $d['sku'] ?: null,
                $d['price_override'] !== null && $d['price_override'] !== '' ? (int) $d['price_override'] : null,
                (int) $d['stock'],
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateVariant(int $id, array $d): void
    {
        Database::query(
            'UPDATE product_variants SET size=?, color=?, sku=?, price_override=?, stock=?, is_active=? WHERE id=?',
            [
                $d['size'] ?: null,
                $d['color'] ?: null,
                $d['sku'] ?: null,
                $d['price_override'] !== null && $d['price_override'] !== '' ? (int) $d['price_override'] : null,
                (int) $d['stock'],
                !empty($d['is_active']) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function deleteVariant(int $id): void
    {
        Database::query('DELETE FROM product_variants WHERE id = ?', [$id]);
    }

    // -------------------------------------------------------------- Images

    public static function images(int $productId): array
    {
        return Database::all(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id',
            [$productId]
        );
    }

    public static function addImage(int $productId, string $file, int $sort = 0): int
    {
        Database::query(
            'INSERT INTO product_images (product_id, file, sort_order) VALUES (?, ?, ?)',
            [$productId, $file, $sort]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function deleteImage(int $id): void
    {
        Database::query('DELETE FROM product_images WHERE id = ?', [$id]);
    }

    public static function setPrimaryImage(int $productId, int $imageId): void
    {
        Database::transaction(static function () use ($productId, $imageId): void {
            $target = Database::one('SELECT id FROM product_images WHERE id = ? AND product_id = ?', [$imageId, $productId]);
            if ($target === null) {
                return;
            }
            $min = (int) Database::value(
                'SELECT COALESCE(MIN(sort_order), 0) FROM product_images WHERE product_id = ?',
                [$productId]
            );
            if ($min <= 0) {
                Database::query('UPDATE product_images SET sort_order = sort_order + 10 WHERE product_id = ?', [$productId]);
                $min = 0;
            }
            Database::query('UPDATE product_images SET sort_order = ? WHERE id = ?', [$min - 1, $imageId]);
        });
    }

    // -------------------------------------------------------------- Stats

    public static function dashboardStats(): array
    {
        $revenue = (int) (Database::value(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ('NEW','CONFIRMED','PROCESSING','READY','DELIVERED')"
        ) ?? 0);
        $todayOrders = (int) (Database::value(
            'SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()'
        ) ?? 0);
        $pending = (int) (Database::value("SELECT COUNT(*) FROM orders WHERE status = 'NEW'") ?? 0);
        $products = (int) (Database::value('SELECT COUNT(*) FROM products') ?? 0);
        $activeProducts = (int) (Database::value("SELECT COUNT(*) FROM products WHERE status = 'active'") ?? 0);
        $lowStock = (int) (Database::value(
            'SELECT COUNT(*) FROM (SELECT product_id FROM product_variants WHERE is_active = 1 GROUP BY product_id HAVING SUM(stock) <= 3 AND SUM(stock) > 0) t'
        ) ?? 0);
        $outOfStock = (int) (Database::value(
            'SELECT COUNT(*) FROM (SELECT product_id FROM product_variants WHERE is_active = 1 GROUP BY product_id HAVING SUM(stock) = 0) t'
        ) ?? 0);
        $customers = (int) (Database::value('SELECT COUNT(*) FROM customers') ?? 0);

        return [
            'revenue' => $revenue,
            'today_orders' => $todayOrders,
            'pending_orders' => $pending,
            'products' => $products,
            'active_products' => $activeProducts,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'customers' => $customers,
        ];
    }

    public static function recentOrders(int $limit = 6): array
    {
        return Database::all(
            "SELECT o.id, o.reference, o.total, o.status, o.created_at, c.name AS customer_name
             FROM orders o JOIN customers c ON c.id = o.customer_id
             ORDER BY o.created_at DESC LIMIT " . max(1, min(20, $limit))
        );
    }

    public static function lowStockProducts(int $limit = 8): array
    {
        return Database::all(
            "SELECT p.id, p.name, p.slug, p.status,
                    (SELECT COALESCE(SUM(v.stock), 0) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS stock
             FROM products p
             WHERE p.status = 'active'
             HAVING stock <= 5
             ORDER BY stock ASC, p.name ASC LIMIT " . max(1, min(30, $limit))
        );
    }
}
