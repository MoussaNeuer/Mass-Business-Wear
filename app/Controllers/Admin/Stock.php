<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Request;
use App\Helpers\Url;
use App\Repositories\AdminProductRepository;

/**
 * Vue Stock (Sprint D) : toutes les variantes, alertes rupture/faible,
 * mouvements récents. Lecture seule — l'édition se fait sur le produit.
 */
final class Stock extends BaseAdmin
{
    public function index(): never
    {
        $f = [
            'q' => trim(Request::str('q', '')),
            'low' => Request::str('low', '') === '1',
        ];
        $variants = AdminProductRepository::stockList($f);
        $movements = AdminProductRepository::recentMovements(15);
        self::render('admin/stock/index', [
            'variants' => $variants,
            'movements' => $movements,
            'filters' => $f,
            'lowCount' => AdminProductRepository::lowStockCount(),
        ], 'Stock');
    }
}
