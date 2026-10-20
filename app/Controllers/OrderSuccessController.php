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
        $photos = [];

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
                $orderId = (int) $order['id'];

                // Articles + miniature du produit pour un récap lisible
                $items = Database::all(
                    'SELECT oi.product_id, oi.variant_id, oi.product_name, oi.size, oi.color,
                            oi.unit_price, oi.quantity,
                            (SELECT i.file FROM product_images i
                              WHERE i.product_id = oi.product_id
                              ORDER BY i.sort_order, i.id LIMIT 1) AS image
                     FROM order_items oi
                     WHERE oi.order_id = ?
                     ORDER BY oi.id ASC',
                    [$orderId]
                );

                // Photos de référence jointes par le client (Sprint C)
                $photos = Database::all(
                    'SELECT id, file FROM order_images WHERE order_id = ? ORDER BY id ASC',
                    [$orderId]
                );
            }
        }

        if ($order === null) {
            // Lien expiré / faute de frappe : page gentille avec CTA boutique
            View::echo('shop/order-success', [
                'title' => 'Commande — MASS BUSINESS WEAR',
                'order' => null,
                'items' => [],
                'photos' => [],
                'currency' => SettingRepository::currency(),
                'whatsapp' => SettingRepository::whatsapp(),
            ], 'layouts/shop');
        }

        $currency = SettingRepository::currency();
        $photosCount = count($photos);

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
            'photos' => $photos,
            'currency' => $currency,
            'whatsapp' => $waUrl,
        ], 'layouts/shop');
    }
}
