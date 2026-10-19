<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/** Requêtes admin commandes (liste, détail, changement de statut). */
final class AdminOrderRepository
{
    public const PER_PAGE = 20;
    public const STATUSES = ['NEW', 'CONFIRMED', 'PROCESSING', 'READY', 'DELIVERED', 'CANCELLED'];

    public const STATUS_LABELS = [
        'NEW' => 'Nouvelle',
        'CONFIRMED' => 'Confirmée',
        'PROCESSING' => 'En préparation',
        'READY' => 'Prête',
        'DELIVERED' => 'Livrée',
        'CANCELLED' => 'Annulée',
    ];

    /**
     * @param array{status?:string,q?:string,page?:int} $f
     * @return array{items:array,total:int,pages:int,page:int}
     */
    public static function list(array $f): array
    {
        $where = [];
        $params = [];

        if (!empty($f['status']) && in_array($f['status'], self::STATUSES, true)) {
            $where[] = 'AND o.status = ?';
            $params[] = $f['status'];
        }
        if (!empty($f['q'])) {
            $where[] = 'AND (o.reference LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string) $f['q']) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $whereSql = $where === [] ? '' : ' ' . implode(' ', $where);

        $total = (int) (Database::value(
            'SELECT COUNT(*) FROM orders o JOIN customers c ON c.id = o.customer_id WHERE 1=1' . $whereSql,
            $params
        ) ?? 0);
        $page = max(1, (int) ($f['page'] ?? 1));
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $items = Database::all(
            "SELECT o.id, o.reference, o.total, o.shipping, o.subtotal, o.status, o.zone, o.notes,
                    o.created_at, o.updated_at,
                    c.name AS customer_name, c.phone AS customer_phone,
                    (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE 1=1{$whereSql}
             ORDER BY o.created_at DESC
             LIMIT {$offset}, " . self::PER_PAGE,
            $params
        );

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function find(int $id): ?array
    {
        return Database::one(
            "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone
             FROM orders o JOIN customers c ON c.id = o.customer_id
             WHERE o.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function items(int $orderId): array
    {
        return Database::all(
            'SELECT * FROM order_items WHERE order_id = ? ORDER BY id',
            [$orderId]
        );
    }

    public static function history(int $orderId): array
    {
        return Database::all(
            'SELECT h.*, a.login AS admin_login
             FROM order_status_history h
             LEFT JOIN admins a ON a.id = h.admin_id
             WHERE h.order_id = ?
             ORDER BY h.created_at DESC, h.id DESC',
            [$orderId]
        );
    }

    /** Change le statut + écrit l'historique (CDC §10) dans une transaction. */
    public static function updateStatus(int $orderId, string $newStatus, ?string $note, ?int $adminId): bool
    {
        if (!in_array($newStatus, self::STATUSES, true)) {
            return false;
        }

        return Database::transaction(static function () use ($orderId, $newStatus, $note, $adminId): bool {
            $order = Database::one('SELECT status FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
            if ($order === null) {
                return false;
            }
            $old = (string) $order['status'];
            if ($old === $newStatus) {
                return true; // no-op
            }

            // Si annulation : remettre le stock
            if ($newStatus === 'CANCELLED' && $old !== 'CANCELLED') {
                $lines = Database::all('SELECT variant_id, quantity FROM order_items WHERE order_id = ?', [$orderId]);
                foreach ($lines as $line) {
                    if ($line['variant_id'] === null) {
                        continue;
                    }
                    $vid = (int) $line['variant_id'];
                    $qty = (int) $line['quantity'];
                    Database::query('UPDATE product_variants SET stock = stock + ? WHERE id = ?', [$qty, $vid]);
                    $after = (int) Database::value('SELECT stock FROM product_variants WHERE id = ?', [$vid]);
                    Database::query(
                        'INSERT INTO stock_movements (variant_id, change_qty, stock_after, reason, order_id, admin_id)
                         VALUES (?, ?, ?, "cancel", ?, ?)',
                        [$vid, $qty, $after, $orderId, $adminId]
                    );
                }
            }

            // Ré-activation après annulation : re-décrémenter
            if ($old === 'CANCELLED' && $newStatus !== 'CANCELLED') {
                $lines = Database::all('SELECT variant_id, quantity FROM order_items WHERE order_id = ?', [$orderId]);
                foreach ($lines as $line) {
                    if ($line['variant_id'] === null) {
                        continue;
                    }
                    $vid = (int) $line['variant_id'];
                    $qty = (int) $line['quantity'];
                    Database::query('UPDATE product_variants SET stock = stock - ? WHERE id = ?', [$qty, $vid]);
                    $after = (int) Database::value('SELECT stock FROM product_variants WHERE id = ?', [$vid]);
                    Database::query(
                        'INSERT INTO stock_movements (variant_id, change_qty, stock_after, reason, order_id, admin_id)
                         VALUES (?, ?, ?, "order", ?, ?)',
                        [$vid, -$qty, $after, $orderId, $adminId]
                    );
                }
            }

            Database::query('UPDATE orders SET status = ? WHERE id = ?', [$newStatus, $orderId]);
            Database::query(
                'INSERT INTO order_status_history (order_id, from_status, to_status, admin_id, note) VALUES (?, ?, ?, ?, ?)',
                [$orderId, $old, $newStatus, $adminId, $note !== null && $note !== '' ? mb_substr($note, 0, 255) : null]
            );
            return true;
        });
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM orders WHERE id = ?', [$id]);
    }

    public static function countByStatus(): array
    {
        $rows = Database::all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status');
        $out = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        return $out;
    }

    public static function revenueByDay(int $days = 7): array
    {
        return Database::all(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
             FROM orders
             WHERE status != 'CANCELLED' AND created_at >= (CURDATE() - INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$days]
        );
    }

    public static function topProducts(int $limit = 5): array
    {
        return Database::all(
            "SELECT product_name, SUM(quantity) AS qty, SUM(unit_price * quantity) AS revenue
             FROM order_items i
             JOIN orders o ON o.id = i.order_id
             WHERE o.status != 'CANCELLED'
             GROUP BY product_name
             ORDER BY qty DESC
             LIMIT " . max(1, min(20, $limit))
        );
    }
}
