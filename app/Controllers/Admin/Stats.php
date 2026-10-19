<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Repositories\AdminStatsRepository;

/** Statistiques avancées (période 7/30/90 jours + comparaison). */
final class Stats extends BaseAdmin
{
    private const ALLOWED = [7, 30, 90];

    public function index(): never
    {
        $days = Request::int('days', 30);
        if (!in_array($days, self::ALLOWED, true)) {
            $days = 30;
        }

        self::render('admin/stats/index', [
            'days' => $days,
            'summary' => AdminStatsRepository::summary($days),
            'vs' => AdminStatsRepository::vsPrevious($days),
            'byDay' => AdminStatsRepository::revenueByDay($days),
            'byZone' => AdminStatsRepository::byZone($days),
            'topProducts' => AdminStatsRepository::topProducts($days, 10),
            'topCustomers' => AdminStatsRepository::topCustomers($days, 10),
            'statusBreakdown' => AdminStatsRepository::statusBreakdown($days),
            'repeat' => AdminStatsRepository::repeatRate(),
            'monthly' => AdminStatsRepository::monthlyRevenue(6),
        ], 'Statistiques');
    }
}
