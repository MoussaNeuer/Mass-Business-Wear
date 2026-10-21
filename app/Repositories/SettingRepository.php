<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/** Paramètres boutique (table settings), cache statique par requête. */
final class SettingRepository
{
    /**
     * Contact codé en dur (volontairement hors table settings).
     * Ces deux numéros sont la source unique de vérité : toutes les pages
     * boutique, les liens WhatsApp et les messages de commande en dérivent.
     */
    public const WHATSAPP = '767982442';
    public const PHONE = '767982442';

    /** Clés surchargées par les constantes ci-dessus (lecture seule). */
    private const HARDCODED = [
        'whatsapp' => self::WHATSAPP,
        'phone' => self::PHONE,
    ];

    /** @var array<string,string|null>|null */
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            try {
                $rows = Database::all('SELECT `key`, `value` FROM settings');
                self::$cache = array_column($rows, 'value', 'key');
            } catch (\Throwable) {
                self::$cache = [];
            }
            // Les numéros sont codés en dur : on écrase la valeur table.
            foreach (self::HARDCODED as $key => $value) {
                self::$cache[$key] = $value;
            }
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();
        $val = $all[$key] ?? null;
        return ($val === null || $val === '') ? $default : (string) $val;
    }

    /** Numéro WhatsApp boutique, 9 chiffres sans indicatif (ex. 767982442). */
    public static function whatsapp(): string
    {
        return self::WHATSAPP;
    }

    /** Numéro de téléphone boutique, 9 chiffres sans indicatif. */
    public static function phone(): string
    {
        return self::PHONE;
    }

    public static function currency(): string
    {
        return self::get('currency', 'FCFA');
    }
}
