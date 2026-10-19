<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\AdminOrderRepository;
use App\Repositories\AdminProductRepository;

/** Dashboard admin : stats, commandes récentes, alertes stock. */
final class Dashboard extends BaseAdmin
{
    public function index(): never
    {
        self::render('admin/dashboard', [
            'stats' => AdminProductRepository::dashboardStats(),
            'recentOrders' => AdminProductRepository::recentOrders(6),
            'lowStock' => AdminProductRepository::lowStockProducts(8),
            'statusCounts' => AdminOrderRepository::countByStatus(),
            'revenueWeek' => AdminOrderRepository::revenueByDay(7),
            'topProducts' => AdminOrderRepository::topProducts(5),
        ], 'Tableau de bord');
    }
}
