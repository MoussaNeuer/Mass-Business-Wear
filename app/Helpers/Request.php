<?php

declare(strict_types=1);

namespace App\Helpers;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isMethod(string $method): bool
    {
        return self::method() === strtoupper($method);
    }

    /** Chemin de la requête, hors base path, sans query string. */
    public static function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        // Deux entrées possibles : /public/... (docroot direct) ou /... (rewrite racine)
        $base = BaseUrl::path();          // ex. /mass-business-wear/public
        $root = $base !== '' ? dirname($base) : ''; // ex. /mass-business-wear

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        } elseif ($root !== '' && $root !== '/' && str_starts_with($path, $root)) {
            $path = substr($path, strlen($root)) ?: '/';
            if (str_starts_with($path, '/public')) {
                $path = substr($path, strlen('/public')) ?: '/';
            }
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }

        return '/' . ltrim($path, '/');
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function str(string $key, string $default = ''): string
    {
        $val = self::input($key, $default);
        return is_scalar($val) ? (string) $val : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $val = self::input($key);
        return is_numeric($val) ? (int) $val : $default;
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $val = $_SERVER[$key] ?? null;
        return is_string($val) ? $val : null;
    }

    /** Corps JSON d'une requête POST (fetch). Retourne [] si invalide. */
    public static function json(): array
    {
        static $cache = false;
        if ($cache !== false) {
            return $cache;
        }
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return $cache = [];
        }
        $data = json_decode($raw, true);
        return $cache = is_array($data) ? $data : [];
    }

    public static function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function isAjax(): bool
    {
        return self::header('X-Requested-With') === 'XMLHttpRequest'
            || str_contains(self::header('Accept') ?? '', 'application/json');
    }

    public static function wantsJson(): bool
    {
        return str_starts_with(Request::path(), '/api/')
            || str_contains(self::header('Accept') ?? '', 'application/json');
    }
}

final class BaseUrl
{
    public static function path(): string
    {
        static $path = null;
        if ($path !== null) {
            return $path;
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '.' || $dir === '\\') {
            $dir = '';
        }
        return $path = rtrim($dir, '/');
    }

    /**
     * Base URL publique (config APP_URL). Utilisée pour générer les liens.
     */
    public static function url(string $path = ''): string
    {
        $config = App::config();
        $base = rtrim((string) ($config['url'] ?? ''), '/');
        if ($path === '' || $path === '/') {
            return $base . '/';
        }
        return $base . '/' . ltrim($path, '/');
    }
}

final class App
{
    /** @var array<string,mixed> */
    private static array $config = [];

    public static function boot(array $config): void
    {
        self::$config = $config;
        date_default_timezone_set((string) ($config['timezone'] ?? 'UTC'));

        if (!empty($config['debug'])) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }
    }

    /** @return array<string,mixed> */
    public static function config(string $key = ''): mixed
    {
        if ($key === '') {
            return self::$config;
        }
        return self::$config[$key] ?? null;
    }

    public static function debug(): bool
    {
        return !empty(self::$config['debug']);
    }
}
