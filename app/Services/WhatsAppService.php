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
     * @param list<array{product_name:string,size?:?string,color?:?string,unit_price:int,quantity:int,line_total:int}> $items
     */
    public static function orderLink(
        string $reference,
        string $customerName,
        string $customerPhone,
        string $zone,
        string $address,
        array $items,
        int $total
    ): string {
        $shop = self::shopNumber();
        $lines = [
            '🛍️ *NOUVELLE COMMANDE* — MASS BUSINESS WEAR',
            '',
            'Référence : *' . $reference . '*',
            'Client : ' . $customerName,
            'Téléphone : ' . Format::phonePretty($customerPhone),
            'Zone : ' . $zone,
            'Adresse : ' . $address,
            '',
            '*Articles :*',
        ];

        foreach ($items as $item) {
            $label = '- ' . $item['product_name'];
            $meta = trim(($item['size'] ?? '') . ' ' . ($item['color'] ?? ''));
            if ($meta !== '') {
                $label .= ' (' . $meta . ')';
            }
            $label .= ' × ' . (int) $item['quantity'] . ' = '
                . number_format((int) $item['line_total'], 0, ',', ' ') . ' FCFA';
            $lines[] = $label;
        }

        $lines[] = '';
        $lines[] = '*Total : ' . number_format($total, 0, ',', ' ') . ' FCFA*';
        $lines[] = '';
        $lines[] = 'Merci de confirmer cette commande.';

        $text = implode("\n", $lines);
        $url = 'https://wa.me/' . ($shop !== '' ? $shop : '') . '?text=' . rawurlencode($text);
        return $url;
    }

    /** Lien générique (contact / partage). */
    public static function link(string $text): string
    {
        $shop = self::shopNumber();
        return 'https://wa.me/' . ($shop !== '' ? $shop : '') . '?text=' . rawurlencode($text);
    }
}
