<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Repositories\SettingRepository;

/**
 * Page succès après commande — Phase 4.
 * Affiche la référence + bouton WhatsApp de confirmation.
 */
final class OrderSuccessController
{
    public function index(): never
    {
        $ref = trim(Request::str('ref', ''));
        $order = null;
        $customer = null;
        $items = [];

        if ($ref !== '' && preg_match('/^MBW-[A-Z0-9]{6,12}-\d{3}$/', $ref) === 1) {
            $order = Database::one(
                'SELECT o.id, o.reference, o.total, o.shipping, o.subtotal, o.status, o.zone, o.address,
                        o.created_at, c.name AS customer_name, c.phone AS customer_phone
                 FROM orders o
                 JOIN customers c ON c.id = o.customer_id
                 WHERE o.reference = ?
                 LIMIT 1',
                [$ref]
            );
            if ($order !== null) {
                $items = Database::all(
                    'SELECT product_name, size, color, unit_price, quantity
                     FROM order_items WHERE order_id = ? ORDER BY id ASC',
                    [(int) $order['id']]
                );
            }
        }

        if ($order === null) {
            // Lien expiré / faute de frappe : page gentille avec CTA boutique
            View::echo('shop/order-success', [
                'title' => 'Commande — MASS BUSINESS WEAR',
                'order' => null,
                'items' => [],
                'currency' => SettingRepository::currency(),
                'whatsapp' => SettingRepository::whatsapp(),
            ], 'layouts/shop');
        }

        $currency = SettingRepository::currency();
        $waLines = [
            'Confirmation commande ' . $order['reference'],
            'Client : ' . $order['customer_name'],
            'Total : ' . number_format((int) $order['total'], 0, ',', ' ') . ' ' . $currency,
        ];
        foreach ($items as $it) {
            $meta = trim(($it['size'] ?? '') . ' ' . ($it['color'] ?? ''));
            $waLines[] = '- ' . $it['product_name'] . ($meta !== '' ? ' (' . $meta . ')' : '')
                . ' ×' . (int) $it['quantity'];
        }
        $waText = implode("\n", $waLines);
        $waShop = preg_replace('/\D+/', '', (string) SettingRepository::whatsapp());
        $waUrl = 'https://wa.me/' . ($waShop !== '' ? '221' . $waShop : '')
            . '?text=' . rawurlencode($waText);

        View::echo('shop/order-success', [
            'title' => 'Commande ' . $order['reference'] . ' — MASS BUSINESS WEAR',
            'order' => $order,
            'items' => $items,
            'currency' => $currency,
            'whatsapp' => $waUrl,
        ], 'layouts/shop');
    }
}
