<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Repositories\SettingRepository;
use App\Services\WhatsAppService;

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
                        o.notes, o.created_at, c.name AS customer_name, c.phone AS customer_phone
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

        // Nombre de photos de référence jointes (Sprint C)
        $photosCount = (int) (Database::value(
            'SELECT COUNT(*) FROM order_images WHERE order_id = ?',
            [(int) $order['id']]
        ) ?? 0);

        // Libellé de zone lisible (la colonne peut contenir un ancien id)
        $zone = (string) $order['zone'];
        if ($zone !== '' && ctype_digit($zone)) {
            $label = Database::value('SELECT name FROM shipping_zones WHERE id = ? LIMIT 1', [(int) $zone]);
            if (is_string($label) && $label !== '') {
                $zone = $label;
            }
        }
        $order['zone'] = $zone; // la vue affiche le libellé, pas l'id

        $waUrl = WhatsAppService::orderLink(
            (string) $order['reference'],
            (string) $order['customer_name'],
            (string) $order['customer_phone'],
            $zone,
            (string) $order['address'],
            array_map(static function (array $it): array {
                $it['line_total'] = (int) $it['unit_price'] * (int) $it['quantity'];
                return $it;
            }, $items),
            (int) $order['total'],
            [
                'subtotal' => (int) $order['subtotal'],
                'shipping' => (int) $order['shipping'],
                'currency' => $currency,
                'notes' => (string) ($order['notes'] ?? ''),
                'created_at' => (string) $order['created_at'],
                'photos' => $photosCount,
            ]
        );

        View::echo('shop/order-success', [
            'title' => 'Commande ' . $order['reference'] . ' — MASS BUSINESS WEAR',
            'order' => $order,
            'items' => $items,
            'currency' => $currency,
            'whatsapp' => $waUrl,
        ], 'layouts/shop');
    }
}
