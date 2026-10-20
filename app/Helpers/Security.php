<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;

final class Security
{
    public static function bootSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = App::config('session');
        $name = is_array($config) ? (string) ($config['name'] ?? 'mbw_sess') : 'mbw_sess';
        $lifetime = is_array($config) ? (int) ($config['lifetime'] ?? 7200) : 7200;

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }

    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /** CSP permissive pour Bootstrap inline + data: (images base64), sans CDN. */
    public static function cspHeaders(): void
    {
        $csp = implode('; ', [
            "default-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
        header('Content-Security-Policy: ' . $csp);
    }

    // ---------------------------------------------------------------- CSRF

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="'
            . htmlspecialchars(self::csrfToken(), ENT_QUOTES) . '">';
    }

    public static function csrfMeta(): string
    {
        return '<meta name="csrf-token" content="'
            . htmlspecialchars(self::csrfToken(), ENT_QUOTES) . '">';
    }

    public static function verifyCsrf(?string $token): bool
    {
        $stored = $_SESSION['csrf_token'] ?? '';
        if (!is_string($stored) || $stored === '' || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /** Vérifie le CSRF sur toutes les méthodes de mutation. Abort si invalide. */
    public static function checkCsrf(): void
    {
        if (in_array(Request::method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = Request::header('X-CSRF-Token') ?? (string) Request::post('_token', '');
        if (!self::verifyCsrf($token)) {
            // 403 (et non 419) : Apache 2.4 remplace les codes non standards par 500
            Response::abort(403, 'Jeton de sécurité invalide ou expiré. Rechargez la page.');
        }
    }

    // ------------------------------------------------------------- Sessions

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // ------------------------------------------------------------- Divers

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Normalise un numéro sénégalais en 9 chiffres (771234567). */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00221')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '221') && strlen($digits) === 12) {
            $digits = substr($digits, 3);
        }
        if (!preg_match('/^[237]\d{8}$/', $digits)) {
            return null;
        }
        return $digits;
    }

    public static function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;
        return trim($text, '-');
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function isAdmin(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function adminId(): ?int
    {
        return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    }

    public static function adminLogin(): string
    {
        return isset($_SESSION['admin_login']) ? (string) $_SESSION['admin_login'] : '';
    }
}

final class Money
{
    public static function format(int|float|string|null $amount, string $currency = 'FCFA'): string
    {
        $amount = (int) $amount;
        return number_format($amount, 0, ',', ' ') . ' ' . $currency;
    }
}

final class Format
{
    public static function date(?string $datetime, string $format = 'd/m/Y H:i'): string
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }
        $ts = strtotime($datetime);
        return $ts === false ? '—' : date($format, $ts);
    }

    public static function phoneWa(string $phone): string
    {
        return '221' . $phone;
    }

    public static function phonePretty(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 9) {
            // Format sénégalais sur 9 chiffres : 77 123 45 67
            return substr($digits, 0, 2) . ' '
                . substr($digits, 2, 3) . ' '
                . substr($digits, 5, 2) . ' '
                . substr($digits, 7, 2);
        }
        return $phone;
    }
}
