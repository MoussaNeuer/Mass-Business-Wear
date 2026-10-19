<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

final class ShippingRepository
{
    /** Zones actives, triées, pour le checkout. */
    public static function active(): array
    {
        return Database::all(
            'SELECT id, name, price FROM shipping_zones WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
    }
}
