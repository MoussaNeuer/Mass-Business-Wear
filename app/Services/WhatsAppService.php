<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Format;
use App\Repositories\SettingRepository;

/**
 * Construction des liens / messages WhatsApp (Phase 4).
 * Aucune dépendance externe : on encode l'URL et on laisse wa.me afficher.
 */
final class WhatsAppService
{
    /** Numéro boutique en format international 221… (ou vide). */
    public static function shopNumber(): string
    {
        $raw = SettingRepository::whatsapp();
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, '221')) {
            return $raw;
        }
        return '221' . $raw;
    }

    /**
     * Lien wa.me pré-rempli pour la confirmation d'une commande.
     *
     * $extra accepte, de façon optionnelle (rétro-compatibilité) :
     *   - subtotal:int       sous-total articles
     *   - shipping:int       frais de livraison
     *   - currency:string    devise (défaut : celle des réglages)
     *   - notes:string       note du client
     *   - created_at:string  date/heure de commande (MySQL ou ISO)
     *   - photos:int         nombre de photos de référence jointes
     *   - order_url:string   lien de suivi de la commande
     *
     * @param list<array{product_name:string,size?:?string,color?:?string,unit_price:int,quantity:int,line_total?:int}> $items
     */
    public static function orderLink(
        string $reference,
        string $customerName,
        string $customerPhone,
        string $zone,
        string $address,
        array $items,
        int $total,
        array $extra = []
    ): string {
        $text = self::orderMessage(
            $reference,
            $customerName,
            $customerPhone,
            $zone,
            $address,
            $items,
            $total,
            $extra
        );

        $shop = self::shopNumber();
        return 'https://wa.me/' . $shop . '?text=' . rawurlencode($text);
    }

    /**
     * Message de commande (séparé du lien pour pouvoir l'afficher / le tester).
     *
     * @param list<array{product_name:string,size?:?string,color?:?string,unit_price:int,quantity:int,line_total?:int}> $items
     */
    public static function orderMessage(
        string $reference,
        string $customerName,
        string $customerPhone,
        string $zone,
        string $address,
        array $items,
        int $total,
        array $extra = []
    ): string {
        $currency = (string) ($extra['currency'] ?? SettingRepository::currency());
        $subtotal = (int) ($extra['subtotal'] ?? 0);
        $shipping = (int) ($extra['shipping'] ?? 0);
        $notes = trim((string) ($extra['notes'] ?? ''));
        $photos = (int) ($extra['photos'] ?? 0);
        $orderUrl = trim((string) ($extra['order_url'] ?? ''));
        $date = self::dateLine((string) ($extra['created_at'] ?? ''));

        $money = static fn (int $n): string => number_format($n, 0, ',', ' ') . ' ' . $currency;

        $lines = [];
        $lines[] = '🛍️ *NOUVELLE COMMANDE*';
        $lines[] = self::shopName();
        $lines[] = '━━━━━━━━━━━━━━━';
        $lines[] = '';

        // ---------------------------------------------------------- Repères
        $lines[] = '📋 *Référence* : `' . $reference . '`';
        if ($date !== '') {
            $lines[] = '📅 ' . $date;
        }
        $lines[] = '';

        // ---------------------------------------------------------- Client
        $lines[] = '👤 *CLIENT*';
        $lines[] = '• Nom : ' . $customerName;
        $lines[] = '• Tél : ' . Format::phonePretty($customerPhone);
        $lines[] = '';

        // ------------------------------------------------------- Livraison
        $lines[] = '🚚 *LIVRAISON*';
        $lines[] = '• Zone : ' . ($zone !== '' ? $zone : 'À préciser');
        $lines[] = '• Adresse : ' . ($address !== '' ? $address : 'À préciser');
        if ($shipping > 0) {
            $lines[] = '• Frais de livraison : ' . $money($shipping);
        } else {
            $lines[] = '• Frais de livraison : offerts / retrait';
        }
        if ($photos > 0) {
            $lines[] = '• 📎 ' . $photos . ' photo(s) de référence jointe(s) à la commande';
        }
        $lines[] = '';

        // -------------------------------------------------------- Articles
        $lines[] = '🛒 *ARTICLES*';
        if ($items === []) {
            $lines[] = '_(détail disponible dans le récapitulatif)_';
        }
        foreach ($items as $i => $item) {
            $qty = (int) $item['quantity'];
            $unit = (int) $item['unit_price'];
            $lineTotal = (int) ($item['line_total'] ?? ($unit * $qty));

            $lines[] = '';
            $lines[] = ($i + 1) . '. ' . $item['product_name'];
            $meta = trim(($item['size'] ?? '') . ' ' . ($item['color'] ?? ''));
            if ($meta !== '') {
                $lines[] = '   ↳ ' . $meta;
            }
            $lines[] = '   ' . $qty . ' × ' . $money($unit) . ' = *' . $money($lineTotal) . '*';
        }
        $lines[] = '';

        // --------------------------------------------------------- Finances
        $lines[] = '━━━━━━━━━━━━━━━';
        if ($subtotal > 0) {
            $lines[] = 'Sous-total : ' . $money($subtotal);
        }
        if ($shipping > 0) {
            $lines[] = 'Livraison : ' . $money($shipping);
        }
        $lines[] = '💰 *TOTAL À PAYER : ' . $money($total) . '*';
        $lines[] = 'Mode de paiement : à la livraison';
        $lines[] = '';

        if ($notes !== '') {
            $lines[] = '📝 *Note du client*';
            $lines[] = $notes;
            $lines[] = '';
        }

        if ($orderUrl !== '') {
            $lines[] = '🔗 Suivi de commande : ' . $orderUrl;
            $lines[] = '';
        }

        // ------------------------------------------------- Instructions
        $lines[] = '━━━━━━━━━━━━━━━';
        $lines[] = '✅ *À faire*';
        $lines[] = '1. Vérifier la disponibilité des articles';
        $lines[] = '2. Appeler le client pour confirmer';
        $lines[] = '3. Convenir du créneau de livraison';
        $lines[] = '';
        $lines[] = '🙏 Merci de votre confiance — *MASS BUSINESS WEAR*';
        $lines[] = 'Référence à conserver : `' . $reference . '`';

        return implode("\n", $lines);
    }

    /** Nom de la boutique depuis les réglages. */
    private static function shopName(): string
    {
        $name = trim(SettingRepository::get('shop_name'));
        return $name !== '' ? $name : 'MASS BUSINESS WEAR';
    }

    /** Date lisible, ou chaîne vide si absente/invalide. */
    private static function dateLine(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return '';
        }
        return date('d/m/Y à H:i', $ts);
    }

    /** Lien générique (contact / partage). */
    public static function link(string $text): string
    {
        $shop = self::shopNumber();
        return 'https://wa.me/' . $shop . '?text=' . rawurlencode($text);
    }
}
