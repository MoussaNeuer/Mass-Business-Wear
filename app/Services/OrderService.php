<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Security;
use App\Repositories\SettingRepository;
use RuntimeException;

/**
 * Validation serveur d'une commande (Phase 4).
 * Règles : le prix / le stock / la variante viennent TOUJOURS de la base,
 * jamais du navigateur. Transaction atomique + idempotence.
 */
final class OrderService
{
    public const MAX_ITEMS = 30;
    public const MAX_QTY = 20;
    /** Photos de référence jointes à la commande (Sprint C). */
    public const MAX_IMAGES = 3;

    /**
     * @param array{
     *   name:string, phone:string, zone:string, address:string, note?:string,
     *   items:list<array{product_id:int,variant_id?:int|null,quantity:int}>
     * } $payload
     * @param ?string $idempotencyKey
     * @param list<array{name:string,type:string,tmp_name:string,error:int,size:int}> $imageFiles
     * @return array{reference:string, whatsapp_url:string, total:int, shipping:int, subtotal:int, images_count?:int}
     */
    public static function create(array $payload, ?string $idempotencyKey = null, array $imageFiles = []): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $phone = Security::normalizePhone((string) ($payload['phone'] ?? ''));
        $zone = trim((string) ($payload['zone'] ?? ''));
        $address = trim((string) ($payload['address'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));
        $items = $payload['items'] ?? [];

        if ($name === '' || mb_strlen($name) > 120) {
            throw new RuntimeException('Nom invalide.');
        }
        if ($phone === null) {
            throw new RuntimeException('Numéro de téléphone invalide (format sénégalais : 7X XXX XXX).');
        }
        if ($zone === '' || mb_strlen($zone) > 100) {
            throw new RuntimeException('Zone de livraison invalide.');
        }
        if ($address === '' || mb_strlen($address) > 255) {
            throw new RuntimeException('Adresse invalide (requis, 255 caractères max).');
        }
        if (!is_array($items) || $items === [] || count($items) > self::MAX_ITEMS) {
            throw new RuntimeException('Panier vide ou trop d\'articles.');
        }

        // Photos de référence (Sprint C) : optionnelles, jamais bloquantes.
        // On valide d'abord SANS rien écrire, puis on persiste après le commit.
        $validImages = [];
        foreach (array_slice($imageFiles, 0, self::MAX_IMAGES) as $file) {
            if (!is_array($file)) {
                continue;
            }
            try {
                ImageService::validate($file);
                $validImages[] = $file;
            } catch (RuntimeException) {
                // photo refusée : la commande passe quand même sans elle
            }
        }

        // Idempotence : même clé → même commande (retry réseau sans double)
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = Database::one(
                'SELECT reference, total, shipping, subtotal FROM orders WHERE idempotency_key = ? LIMIT 1',
                [$idempotencyKey]
            );
            if ($existing !== null) {
                $wa = WhatsAppService::orderLink(
                    (string) $existing['reference'],
                    $name,
                    $phone,
                    self::zoneLabel($zone),
                    $address,
                    [], // déjà envoyé
                    (int) $existing['total']
                );
                return [
                    'reference' => (string) $existing['reference'],
                    'whatsapp_url' => $wa,
                    'total' => (int) $existing['total'],
                    'shipping' => (int) $existing['shipping'],
                    'subtotal' => (int) $existing['subtotal'],
                    'duplicate' => true,
                    'images_count' => 0,
                ];
            }
        }

        $result = Database::transaction(static function () use ($name, $phone, $zone, $address, $note, $items, $idempotencyKey): array {
            // Verrouillage des lignes de stock pour éviter la double décrémentation
            $resolved = [];
            $subtotal = 0;

            foreach ($items as $raw) {
                $productId = (int) ($raw['product_id'] ?? 0);
                $variantId = isset($raw['variant_id']) && $raw['variant_id'] !== null && $raw['variant_id'] !== ''
                    ? (int) $raw['variant_id']
                    : null;
                $qty = (int) ($raw['quantity'] ?? 0);

                if ($productId <= 0 || $qty <= 0 || $qty > self::MAX_QTY) {
                    throw new RuntimeException('Article invalide dans le panier.');
                }

                $product = Database::one(
                    "SELECT id, name, slug, price, promo_price, status
                     FROM products WHERE id = ? AND status = 'active' FOR UPDATE",
                    [$productId]
                );
                if ($product === null) {
                    throw new RuntimeException('Un produit n\'est plus disponible.');
                }

                $unitPrice = $product['promo_price'] !== null ? (int) $product['promo_price'] : (int) $product['price'];
                $size = null;
                $color = null;

                if ($variantId !== null) {
                    $variant = Database::one(
                        'SELECT id, size, color, price_override, stock
                         FROM product_variants WHERE id = ? AND product_id = ? AND is_active = 1 FOR UPDATE',
                        [$variantId, $productId]
                    );
                    if ($variant === null) {
                        throw new RuntimeException('Une variante n\'est plus disponible.');
                    }
                    if ((int) $variant['stock'] < $qty) {
                        throw new RuntimeException(sprintf(
                            'Stock insuffisant pour %s (%s).',
                            $product['name'],
                            trim((string) $variant['size'] . ' ' . (string) $variant['color'])
                        ));
                    }
                    if ($variant['price_override'] !== null) {
                        $unitPrice = (int) $variant['price_override'];
                    }
                    $size = $variant['size'];
                    $color = $variant['color'];
                } else {
                    // Sans variante on ne sait pas quel stock décrémenter : refus.
                    // (Le parcours normal envoie toujours variant_id depuis la fiche produit.)
                    $hasVariants = (int) Database::value(
                        'SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND is_active = 1',
                        [$productId]
                    );
                    throw new RuntimeException($hasVariants > 0
                        ? 'Choisissez une taille / couleur pour ' . $product['name'] . '.'
                        : 'Ce produit n\'est plus disponible.');
                }

                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $resolved[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'product_name' => (string) $product['name'],
                    'size' => $size,
                    'color' => $color,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            // Livraison : soit un montant fixe depuis la zone, soit 0
            $shipping = self::shippingForZone($zone);
            $zoneLabel = self::zoneLabel($zone);
            $total = $subtotal + $shipping;

            // Client (upsert par téléphone)
            $customerId = self::upsertCustomer($name, $phone);

            // Référence : MBW-AAAAMMJJ-001
            $reference = self::nextReference();

            $stmt = Database::query(
                'INSERT INTO orders
                    (reference, idempotency_key, customer_id, address, zone, subtotal, shipping, total, status, notes, source)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, "NEW", ?, "web")',
                [
                    $reference,
                    $idempotencyKey !== null && $idempotencyKey !== '' ? substr($idempotencyKey, 0, 64) : null,
                    $customerId,
                    $address,
                    $zone,
                    $subtotal,
                    $shipping,
                    $total,
                    $note !== '' ? $note : null,
                ]
            );
            $orderId = (int) Database::pdo()->lastInsertId();

            foreach ($resolved as $line) {
                Database::query(
                    'INSERT INTO order_items
                        (order_id, product_id, variant_id, product_name, size, color, unit_price, quantity)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $orderId,
                        $line['product_id'],
                        $line['variant_id'],
                        $line['product_name'],
                        $line['size'],
                        $line['color'],
                        $line['unit_price'],
                        $line['quantity'],
                    ]
                );

                if ($line['variant_id'] !== null) {
                    Database::query(
                        'UPDATE product_variants SET stock = stock - ? WHERE id = ?',
                        [$line['quantity'], $line['variant_id']]
                    );
                    $after = (int) Database::value('SELECT stock FROM product_variants WHERE id = ?', [$line['variant_id']]);
                    Database::query(
                        'INSERT INTO stock_movements (variant_id, change_qty, stock_after, reason, order_id)
                         VALUES (?, ?, ?, "order", ?)',
                        [$line['variant_id'], -$line['quantity'], $after, $orderId]
                    );
                }
            }

            Database::query(
                'INSERT INTO order_status_history (order_id, from_status, to_status, note)
                 VALUES (?, NULL, "NEW", "Commande créée depuis la boutique")',
                [$orderId]
            );

            return [
                'reference' => $reference,
                'order_id' => $orderId,
                'whatsapp_url' => WhatsAppService::orderLink(
                    $reference,
                    $name,
                    $phone,
                    $zoneLabel,
                    $address,
                    $resolved,
                    $total
                ),
                'total' => $total,
                'shipping' => $shipping,
                'subtotal' => $subtotal,
                'duplicate' => false,
            ];
        });

        // Photos de référence (Sprint C) : écriture APRÈS le commit —
        // une image refusée/échouée ne fait jamais rater la commande.
        $saved = 0;
        $orderId = (int) ($result['order_id'] ?? 0);
        if ($orderId > 0 && $validImages !== []) {
            foreach ($validImages as $file) {
                try {
                    $name = ImageService::saveOrderImage($file);
                    Database::query(
                        'INSERT INTO order_images (order_id, file) VALUES (?, ?)',
                        [$orderId, $name]
                    );
                    $saved++;
                } catch (\Throwable $e) {
                    error_log('[order-image] ' . $e->getMessage());
                }
            }
        }
        $result['images_count'] = $saved;

        return $result;
    }

    private static function shippingForZone(string $zone): int
    {
        // La zone est un int (id) côté checkout ; on accepte aussi un nom libre
        if (ctype_digit($zone)) {
            $row = Database::one(
                'SELECT price FROM shipping_zones WHERE id = ? AND is_active = 1 LIMIT 1',
                [(int) $zone]
            );
            if ($row !== null) {
                return (int) $row['price'];
            }
        }
        $named = Database::one(
            'SELECT price FROM shipping_zones WHERE name = ? AND is_active = 1 LIMIT 1',
            [$zone]
        );
        if ($named !== null) {
            return (int) $named['price'];
        }
        // Repli : paramètre shipping_fee
        return (int) SettingRepository::get('shipping_fee', '0');
    }

    /** Libellé lisible d'une zone (id ou nom). */
    private static function zoneLabel(string $zone): string
    {
        if (ctype_digit($zone)) {
            $row = Database::one(
                'SELECT name FROM shipping_zones WHERE id = ? LIMIT 1',
                [(int) $zone]
            );
            if ($row !== null) {
                return (string) $row['name'];
            }
        }
        return $zone;
    }

    private static function upsertCustomer(string $name, string $phone): int
    {
        $existing = Database::one('SELECT id, name FROM customers WHERE phone = ? LIMIT 1', [$phone]);
        if ($existing !== null) {
            if (strcasecmp((string) $existing['name'], $name) !== 0) {
                Database::query('UPDATE customers SET name = ? WHERE id = ?', [$name, (int) $existing['id']]);
            }
            return (int) $existing['id'];
        }
        Database::query('INSERT INTO customers (name, phone) VALUES (?, ?)', [$name, $phone]);
        return (int) Database::pdo()->lastInsertId();
    }

    /** MBW-AAAAMMJJ-001 — séquence par jour, sous verrou MySQL. */
    private static function nextReference(): string
    {
        $date = date('Ymd');
        $prefix = 'MBW-' . $date . '-';

        $locked = (int) Database::value('SELECT GET_LOCK(?, 5)', ['mbw_ref_' . $date]);
        if ($locked !== 1) {
            throw new RuntimeException('Impossible de générer la référence. Réessayez.');
        }

        try {
            $max = (int) Database::value(
                "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(reference, '-', -1) AS UNSIGNED)), 0)
                 FROM orders WHERE reference LIKE ?",
                [$prefix . '%']
            );
            $seq = str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
            return $prefix . $seq;
        } finally {
            Database::query('SELECT RELEASE_LOCK(?)', ['mbw_ref_' . $date]);
        }
    }
}
