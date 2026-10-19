<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

/** CRUD admin catégories. */
final class AdminCategoryRepository
{
    public static function all(): array
    {
        return Database::all(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS products_count
             FROM categories c
             ORDER BY c.sort_order ASC, c.name ASC"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM categories WHERE id = ? LIMIT 1', [$id]);
    }

    public static function create(array $d): int
    {
        Database::query(
            'INSERT INTO categories (name, slug, description, image, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $d['name'],
                $d['slug'],
                $d['description'] ?: null,
                $d['image'] ?: null,
                !empty($d['is_active']) ? 1 : 0,
                (int) ($d['sort_order'] ?? 0),
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE categories SET name=?, slug=?, description=?, image=?, is_active=?, sort_order=? WHERE id=?',
            [
                $d['name'],
                $d['slug'],
                $d['description'] ?: null,
                $d['image'] ?: null,
                !empty($d['is_active']) ? 1 : 0,
                (int) ($d['sort_order'] ?? 0),
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM categories WHERE id = ?', [$id]);
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = ?';
        $params = [$slug];
        if ($exceptId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $exceptId;
        }
        return (int) Database::value($sql, $params) > 0;
    }
}
