<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;

/**
 * Panier — Phase 3 : rendu réactif localStorage (cart.js).
 */
final class CartController
{
    public function index(): never
    {
        View::echo('shop/cart', [
            'title' => 'Panier — MASS BUSINESS WEAR',
        ], 'layouts/shop');
    }
}
