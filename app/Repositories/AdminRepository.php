<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use PDOException;

final class AdminRepository
{
    public static function findActive(int $id): ?array
    {
        return Database::one(
            'SELECT id, login, password_hash, created_at FROM admins WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    public static function findByLogin(string $login): ?array
    {
        return Database::one(
            'SELECT id, login, password_hash, created_at FROM admins WHERE login = ? LIMIT 1',
            [$login]
        );
    }

    public static function create(string $login, string $passwordHash): int
    {
        Database::query(
            'INSERT INTO admins (login, password_hash) VALUES (?, ?)',
            [$login, $passwordHash]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        Database::query('UPDATE admins SET password_hash = ? WHERE id = ?', [$passwordHash, $id]);
    }

    public static function count(): int
    {
        return (int) (Database::value('SELECT COUNT(*) FROM admins') ?? 0);
    }
}

/**
 * Protection brute force sur la connexion admin.
 * Politique : blocage après N échecs dans la fenêtre + délai progressif.
 */
final class LoginAttemptRepository
{
    public static function record(string $ip, ?string $login): void
    {
        try {
            Database::query(
                'INSERT INTO login_attempts (ip, login) VALUES (?, ?)',
                [$ip, $login !== null ? substr($login, 0, 60) : null]
            );
        } catch (PDOException) {
            // journal non bloquant
        }
    }

    public static function countSince(string $ip, int $seconds): int
    {
        return (int) (Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL ? SECOND)',
            [$ip, $seconds]
        ) ?? 0);
    }

    public static function clear(string $ip): void
    {
        try {
            Database::query('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
        } catch (PDOException) {
        }
    }

    public static function prune(int $seconds = 86400): void
    {
        try {
            Database::query('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL ? SECOND)', [$seconds]);
        } catch (PDOException) {
        }
    }
}

final class LogRepository
{
    public static function write(?int $adminId, string $action, ?string $details = null, ?string $ip = null): void
    {
        try {
            Database::query(
                'INSERT INTO admin_logs (admin_id, action, details, ip) VALUES (?, ?, ?, ?)',
                [$adminId, substr($action, 0, 60), $details !== null ? substr($details, 0, 255) : null, $ip]
            );
        } catch (PDOException) {
            // journal non bloquant
        }
    }
}
