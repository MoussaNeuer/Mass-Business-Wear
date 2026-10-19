<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Repositories\SettingRepository;
use App\Repositories\ShippingRepository;

/**
 * Checkout — Phase 3 : page formulaire + récap (client-side).
 * Phase 4 : POST /api/orders → OrderService (transaction, idempotence) + WhatsApp.
 */
final class CheckoutController
{
    public function index(): never
    {
        View::echo('shop/checkout', [
            'title' => 'Commande — MASS BUSINESS WEAR',
            'currency' => SettingRepository::currency(),
            'whatsapp' => SettingRepository::whatsapp(),
            'shippingZones' => ShippingRepository::active(),
        ], 'layouts/shop');
    }
}
