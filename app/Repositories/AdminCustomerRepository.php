<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/**
 * Clients admin : liste agrégée depuis orders/customers, détail + historique.
 * Pas de table de mise à jour manuelle — les clients sont créés à la commande.
 */
final class AdminCustomerRepository
{
    public const PER_PAGE = 20;

    /**
     * @param array{q?:string,page?:int} $f
     * @return array{items:array,total:int,pages:int,page:int}
     */
    public static function list(array $f): array
    {
        $q = trim((string) ($f['q'] ?? ''));
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = ' WHERE c.name LIKE ? OR c.phone LIKE ?';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }

        $total = (int) (Database::value('SELECT COUNT(*) FROM customers c' . $where, $params) ?? 0);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) ($f['page'] ?? 1)), $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $items = Database::all(
            "SELECT c.id, c.name, c.phone, c.whatsapp, c.created_at,
                    COUNT(o.id) AS orders_count,
                    COALESCE(SUM(CASE WHEN o.status != 'CANCELLED' THEN o.total END), 0) AS total_spent,
                    MAX(o.created_at) AS last_order_at,
                    MAX(o.reference) AS last_reference
             FROM customers c
             LEFT JOIN orders o ON o.customer_id = c.id
             {$where}
             GROUP BY c.id, c.name, c.phone, c.whatsapp, c.created_at
             ORDER BY COALESCE(SUM(CASE WHEN o.status != 'CANCELLED' THEN o.total END), 0) DESC, last_order_at DESC, c.id DESC
             LIMIT {$offset}, " . self::PER_PAGE,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            "SELECT c.*,
                    COUNT(o.id) AS orders_count,
                    COALESCE(SUM(CASE WHEN o.status != 'CANCELLED' THEN o.total END), 0) AS total_spent,
                    MIN(o.created_at) AS first_order_at,
                    MAX(o.created_at) AS last_order_at
             FROM customers c
             LEFT JOIN orders o ON o.customer_id = c.id
             WHERE c.id = ?
             GROUP BY c.id, c.name, c.phone, c.whatsapp, c.created_at",
            [$id]
        );
    }

    public static function orders(int $customerId, int $limit = 50): array
    {
        return Database::all(
            "SELECT o.id, o.reference, o.total, o.status, o.zone, o.created_at,
                    (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count
             FROM orders o
             WHERE o.customer_id = ? AND o.archived_at IS NULL
             ORDER BY o.created_at DESC
             LIMIT " . max(1, min(100, $limit)),
            [$customerId]
        );
    }

    public static function search(string $q, int $limit = 8): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $like = '%' . $q . '%';
        return Database::all(
            "SELECT c.id, c.name, c.phone,
                    COUNT(o.id) AS orders_count,
                    COALESCE(SUM(CASE WHEN o.status != 'CANCELLED' THEN o.total END), 0) AS total_spent
             FROM customers c
             LEFT JOIN orders o ON o.customer_id = c.id
             WHERE c.name LIKE ? OR c.phone LIKE ?
             GROUP BY c.id, c.name, c.phone
             ORDER BY total_spent DESC
             LIMIT " . max(1, min(20, $limit)),
            [$like, $like]
        );
    }
}
