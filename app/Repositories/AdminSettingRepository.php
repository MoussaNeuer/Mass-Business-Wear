<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/** CRUD admin paramètres boutique + zones de livraison + journal. */
final class AdminSettingRepository
{
    public static function all(): array
    {
        $rows = Database::all('SELECT `key`, `value` FROM settings');
        return array_column($rows, 'value', 'key');
    }

    public static function saveMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            Database::query(
                'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
                [(string) $key, (string) $value]
            );
        }
    }

    // ------------------------------------------------------ Zones livraison

    public static function zones(): array
    {
        return Database::all('SELECT * FROM shipping_zones ORDER BY sort_order, name');
    }

    public static function createZone(array $d): int
    {
        Database::query(
            'INSERT INTO shipping_zones (name, price, is_active, sort_order) VALUES (?, ?, ?, ?)',
            [$d['name'], (int) $d['price'], !empty($d['is_active']) ? 1 : 0, (int) ($d['sort_order'] ?? 0)]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateZone(int $id, array $d): void
    {
        Database::query(
            'UPDATE shipping_zones SET name=?, price=?, is_active=?, sort_order=? WHERE id=?',
            [$d['name'], (int) $d['price'], !empty($d['is_active']) ? 1 : 0, (int) ($d['sort_order'] ?? 0), $id]
        );
    }

    public static function deleteZone(int $id): void
    {
        Database::query('DELETE FROM shipping_zones WHERE id = ?', [$id]);
    }

    // ----------------------------------------------------------- Journal

    /**
     * @param array{page?:int,admin_id?:int} $f
     * @return array{items:array,total:int,pages:int,page:int}
     */
    public static function logs(array $f): array
    {
        $where = [];
        $params = [];
        if (!empty($f['admin_id'])) {
            $where[] = 'AND l.admin_id = ?';
            $params[] = (int) $f['admin_id'];
        }
        $whereSql = $where === [] ? '' : ' ' . implode(' ', $where);

        $perPage = 30;
        $total = (int) (Database::value('SELECT COUNT(*) FROM admin_logs l WHERE 1=1' . $whereSql, $params) ?? 0);
        $page = max(1, (int) ($f['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Database::all(
            "SELECT l.*, a.login AS admin_login
             FROM admin_logs l LEFT JOIN admins a ON a.id = l.admin_id
             WHERE 1=1{$whereSql}
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT {$offset}, {$perPage}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function admins(): array
    {
        return Database::all('SELECT id, login FROM admins ORDER BY login');
    }
}
