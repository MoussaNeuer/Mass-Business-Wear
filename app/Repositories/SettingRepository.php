<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/** Paramètres boutique (table settings), cache statique par requête. */
final class SettingRepository
{
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
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();
        $val = $all[$key] ?? null;
        return ($val === null || $val === '') ? $default : (string) $val;
    }

    public static function whatsapp(): string
    {
        return preg_replace('/\D+/', '', self::get('whatsapp')) ?: '';
    }

    public static function currency(): string
    {
        return self::get('currency', 'FCFA');
    }
}
