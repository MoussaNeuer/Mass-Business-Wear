<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SettingRepository;

/**
 * Catalogue public : /shop et /category/{slug}.
 * Filtres en query string (recherche, prix, taille, couleur, tri, page).
 */
final class ShopController
{
    public function index(): never
    {
        $this->render(null);
    }

    public function category(string $slug): never
    {
        $category = CategoryRepository::findBySlug($slug);
        if ($category === null) {
            Response::abort(404, 'Catégorie introuvable.');
        }
        $this->render($category);
    }

    private function render(?array $category): never
    {
        $filters = [
            'q' => trim(Request::str('q', '')),
            'cat' => $category['slug'] ?? '',
            'min' => max(0, Request::int('min', 0)),
            'max' => max(0, Request::int('max', 0)),
            'size' => trim(Request::str('size', '')),
            'color' => trim(Request::str('color', '')),
            'sort' => in_array(Request::str('sort', ''), array_keys(ProductRepository::SORTS), true)
                ? Request::str('sort', '')
                : 'recent',
            'page' => max(1, Request::int('page', 1)),
        ];

        $result = ProductRepository::search($filters);
        $categories = CategoryRepository::activeWithCount();
        $facets = ProductRepository::facets($category['slug'] ?? null);

        // Filtres actifs (chips)
        $chips = [];
        if ($filters['q'] !== '') {
            $chips[] = ['label' => '« ' . $filters['q'] . ' »', 'drop' => 'q'];
        }
        if ($filters['min'] > 0) {
            $chips[] = ['label' => 'Min ' . number_format($filters['min'], 0, ',', ' ') . ' FCFA', 'drop' => 'min'];
        }
        if ($filters['max'] > 0) {
            $chips[] = ['label' => 'Max ' . number_format($filters['max'], 0, ',', ' ') . ' FCFA', 'drop' => 'max'];
        }
        if ($filters['size'] !== '') {
            $chips[] = ['label' => 'Taille ' . $filters['size'], 'drop' => 'size'];
        }
        if ($filters['color'] !== '') {
            $chips[] = ['label' => 'Couleur ' . $filters['color'], 'drop' => 'color'];
        }

        $hasFilters = $chips !== [] || ($category !== null && $filters['q'] === '' && $filters['min'] === 0 && $filters['max'] === 0 && $filters['size'] === '' && $filters['color'] === '');

        View::echo('shop/catalog', [
            'title' => $category !== null
                ? $category['name'] . ' — Boutique'
                : ($filters['q'] !== '' ? 'Recherche : ' . $filters['q'] : 'Boutique — MASS BUSINESS WEAR'),
            'category' => $category,
            'categories' => $categories,
            'filters' => $filters,
            'result' => $result,
            'facets' => $facets,
            'chips' => $chips,
            'hasFilters' => $hasFilters,
            'sorts' => [
                'recent' => 'Nouveautés',
                'popular' => 'Populaires',
                'price_asc' => 'Prix croissant',
                'price_desc' => 'Prix décroissant',
                'name' => 'Nom A→Z',
            ],
            'activeFiltersCount' => count($chips),
            'currency' => SettingRepository::currency(),
            'baseQuery' => array_filter([
                'q' => $filters['q'],
                'min' => $filters['min'] ?: null,
                'max' => $filters['max'] ?: null,
                'size' => $filters['size'],
                'color' => $filters['color'],
                'sort' => $filters['sort'] !== 'recent' ? $filters['sort'] : null,
            ]),
        ], 'layouts/shop');
    }
}
