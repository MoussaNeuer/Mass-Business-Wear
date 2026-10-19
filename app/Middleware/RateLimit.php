<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;

/**
 * Limitation de débit simple, stockée dans storage/cache/ratelimit.json.
 * Assez pour le login et les endpoints publics sur un hébergement mutualisé.
 */
final class RateLimit
{
    private const FILE = 'ratelimit.json';
    private const WINDOW = 900; // 15 minutes
    private const MAX = 180;    // généreux : la recherche tape souvent l'API

    public static function handle(): void
    {
        $bucket = 'global:' . Request::ip();
        if (self::tooMany($bucket, self::MAX, self::WINDOW)) {
            Response::abort(429, 'Trop de requêtes. Réessayez dans quelques minutes.');
        }
    }

    public static function tooMany(string $bucket, int $max, int $window): bool
    {
        $data = self::load();
        $now = time();
        $cutoff = $now - $window;

        $hits = array_filter(
            $data[$bucket] ?? [],
            static fn ($ts) => (int) $ts > $cutoff
        );

        if (count($hits) >= $max) {
            $data[$bucket] = $hits;
            self::save($data);
            return true;
        }

        $hits[] = $now;
        $data[$bucket] = array_values($hits);
        self::save($data);
        return false;
    }

    public static function clear(string $bucket): void
    {
        $data = self::load();
        unset($data[$bucket]);
        self::save($data);
    }

    /** @return array<string,array<int,int>> */
    private static function load(): array
    {
        $file = self::path();
        if (!is_file($file)) {
            return [];
        }
        $json = json_decode((string) file_get_contents($file), true);
        return is_array($json) ? $json : [];
    }

    private static function save(array $data): void
    {
        $file = self::path();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    private static function path(): string
    {
        return dirname(__DIR__, 2) . '/storage/cache/' . self::FILE;
    }
}
