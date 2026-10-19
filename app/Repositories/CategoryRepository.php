<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

final class CategoryRepository
{
    /** Catégories actives, avec comptage des produits publiés. */
    public static function activeWithCount(): array
    {
        return Database::all(
            "SELECT c.id, c.name, c.slug, c.description, c.image,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS products_count
             FROM categories c
             WHERE c.is_active = 1
             ORDER BY c.sort_order ASC, c.name ASC"
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one(
            'SELECT id, name, slug, description, image FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1',
            [$slug]
        );
    }
}
