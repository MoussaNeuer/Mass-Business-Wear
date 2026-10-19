<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/** Statistiques avancées pour la page Stats (période, zones, clients, tendance). */
final class AdminStatsRepository
{
    private const PAID = "('NEW','CONFIRMED','PROCESSING','READY','DELIVERED')";

    /** @return array{orders:int,revenue:int,avg_order:int,items:int,customers:int,new_customers:int,cancelled:int} */
    public static function summary(int $days): array
    {
        $from = (int) $days;
        $orders = (int) (Database::value(
            'SELECT COUNT(*) FROM orders WHERE created_at >= (CURDATE() - INTERVAL ? DAY) AND status IN ' . self::PAID,
            [$from]
        ) ?? 0);
        $revenue = (int) (Database::value(
            'SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at >= (CURDATE() - INTERVAL ? DAY) AND status IN ' . self::PAID,
            [$from]
        ) ?? 0);
        $cancelled = (int) (Database::value(
            "SELECT COUNT(*) FROM orders WHERE created_at >= (CURDATE() - INTERVAL ? DAY) AND status = 'CANCELLED'",
            [$from]
        ) ?? 0);
        $items = (int) (Database::value(
            'SELECT COALESCE(SUM(i.quantity), 0) FROM order_items i JOIN orders o ON o.id = i.order_id
             WHERE o.created_at >= (CURDATE() - INTERVAL ? DAY) AND o.status IN ' . self::PAID,
            [$from]
        ) ?? 0);
        $customers = (int) (Database::value(
            'SELECT COUNT(DISTINCT o.customer_id) FROM orders o
             WHERE o.created_at >= (CURDATE() - INTERVAL ? DAY) AND o.status IN ' . self::PAID,
            [$from]
        ) ?? 0);
        $newCustomers = (int) (Database::value(
            'SELECT COUNT(*) FROM customers WHERE created_at >= (CURDATE() - INTERVAL ? DAY)',
            [$from]
        ) ?? 0);

        return [
            'orders' => $orders,
            'revenue' => $revenue,
            'avg_order' => $orders > 0 ? (int) round($revenue / $orders) : 0,
            'items' => $items,
            'customers' => $customers,
            'new_customers' => $newCustomers,
            'cancelled' => $cancelled,
        ];
    }

    /** Comparaison avec la période précédente de même durée. */
    public static function vsPrevious(int $days): array
    {
        $d = max(1, $days);
        $rows = Database::one(
            "SELECT
                SUM(CASE WHEN created_at >= (CURDATE() - INTERVAL {$d} DAY) AND status IN " . self::PAID . " THEN total ELSE 0 END) AS rev_now,
                SUM(CASE WHEN created_at >= (CURDATE() - INTERVAL " . ($d * 2) . " DAY) AND created_at < (CURDATE() - INTERVAL {$d} DAY) AND status IN " . self::PAID . " THEN total ELSE 0 END) AS rev_prev,
                SUM(CASE WHEN created_at >= (CURDATE() - INTERVAL {$d} DAY) AND status IN " . self::PAID . " THEN 1 ELSE 0 END) AS ord_now,
                SUM(CASE WHEN created_at >= (CURDATE() - INTERVAL " . ($d * 2) . " DAY) AND created_at < (CURDATE() - INTERVAL {$d} DAY) AND status IN " . self::PAID . " THEN 1 ELSE 0 END) AS ord_prev
             FROM orders"
        );
        $revNow = (int) ($rows['rev_now'] ?? 0);
        $revPrev = (int) ($rows['rev_prev'] ?? 0);
        $ordNow = (int) ($rows['ord_now'] ?? 0);
        $ordPrev = (int) ($rows['ord_prev'] ?? 0);

        return [
            'revenue_delta_pct' => self::pct($revNow, $revPrev),
            'orders_delta_pct' => self::pct($ordNow, $ordPrev),
        ];
    }

    /** @return list<array{day:string,orders:int,revenue:int}> */
    public static function revenueByDay(int $days): array
    {
        return Database::all(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
             FROM orders
             WHERE created_at >= (CURDATE() - INTERVAL ? DAY) AND status IN " . self::PAID . "
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [(int) $days]
        );
    }

    /** @return list<array{zone:string,orders:int,revenue:int}> */
    public static function byZone(int $days): array
    {
        return Database::all(
            "SELECT COALESCE(NULLIF(TRIM(zone), ''), 'Non précisée') AS zone,
                    COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
             FROM orders
             WHERE created_at >= (CURDATE() - INTERVAL ? DAY) AND status IN " . self::PAID . "
             GROUP BY COALESCE(NULLIF(TRIM(zone), ''), 'Non précisée')
             ORDER BY revenue DESC
             LIMIT 12",
            [(int) $days]
        );
    }

    /** @return list<array{product_name:string,qty:int,revenue:int}> */
    public static function topProducts(int $days, int $limit = 10): array
    {
        return Database::all(
            "SELECT i.product_name, SUM(i.quantity) AS qty, SUM(i.unit_price * i.quantity) AS revenue
             FROM order_items i
             JOIN orders o ON o.id = i.order_id
             WHERE o.created_at >= (CURDATE() - INTERVAL ? DAY) AND o.status IN " . self::PAID . "
             GROUP BY i.product_name
             ORDER BY qty DESC, revenue DESC
             LIMIT " . max(1, min(20, $limit)),
            [(int) $days]
        );
    }

    /** @return list<array{customer_id:int,name:string,phone:string,orders:int,revenue:int}> */
    public static function topCustomers(int $days, int $limit = 10): array
    {
        return Database::all(
            "SELECT c.id AS customer_id, c.name, c.phone,
                    COUNT(o.id) AS orders, SUM(o.total) AS revenue
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE o.created_at >= (CURDATE() - INTERVAL ? DAY) AND o.status IN " . self::PAID . "
             GROUP BY c.id, c.name, c.phone
             ORDER BY revenue DESC
             LIMIT " . max(1, min(20, $limit)),
            [(int) $days]
        );
    }

    /** @return list<array{status:string,n:int}> */
    public static function statusBreakdown(int $days): array
    {
        return Database::all(
            "SELECT status, COUNT(*) AS n
             FROM orders
             WHERE created_at >= (CURDATE() - INTERVAL ? DAY)
             GROUP BY status
             ORDER BY n DESC",
            [(int) $days]
        );
    }

    /** @return array{repeat:int,one_time:int} Clients avec 2+ commandes vs 1 seule (hors période). */
    public static function repeatRate(): array
    {
        $repeat = (int) (Database::value(
            'SELECT COUNT(*) FROM (SELECT customer_id FROM orders WHERE status != \'CANCELLED\' GROUP BY customer_id HAVING COUNT(*) >= 2) t'
        ) ?? 0);
        $oneTime = (int) (Database::value(
            'SELECT COUNT(*) FROM (SELECT customer_id FROM orders WHERE status != \'CANCELLED\' GROUP BY customer_id HAVING COUNT(*) = 1) t'
        ) ?? 0);
        return ['repeat' => $repeat, 'one_time' => $oneTime];
    }

    /** @return list<array{month:string,revenue:int,orders:int}> */
    public static function monthlyRevenue(int $months = 6): array
    {
        return Database::all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
             FROM orders
             WHERE created_at >= (CURDATE() - INTERVAL ? MONTH) AND status IN " . self::PAID . "
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            [max(1, min(24, $months))]
        );
    }

    private static function pct(int $now, int $prev): ?float
    {
        if ($prev <= 0) {
            return $now > 0 ? null : 0.0;
        }
        return round((($now - $prev) / $prev) * 100, 1);
    }
}
