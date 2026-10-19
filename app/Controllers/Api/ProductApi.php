<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Url;
use App\Repositories\ProductRepository;
use App\Repositories\SettingRepository;

/**
 * API catalogue. Format : { success, message, data }.
 * GET /api/products  — catalogue filtré paginé
 * GET /api/search    — recherche rapide (header)
 */
final class ProductApi
{
    public function index(): never
    {
        $filters = self::filtersFromRequest();
        $result = ProductRepository::search($filters);

        Response::json([
            'items' => array_map([self::class, 'card'], $result['items']),
            'pagination' => [
                'page' => $result['page'],
                'pages' => $result['pages'],
                'total' => $result['total'],
                'per_page' => ProductRepository::PER_PAGE,
            ],
        ], 200, $result['total'] . ' produit(s)');
    }

    public function search(): never
    {
        $q = trim(Request::str('q', ''));
        if (mb_strlen($q) < 2) {
            Response::json(['items' => []], 200, 'Requête trop courte');
        }

        $items = ProductRepository::quickSearch($q, 6);
        Response::json([
            'items' => array_map([self::class, 'card'], $items),
        ], 200, count($items) . ' résultat(s)');
    }

    /** @return array{q:string,cat:string,min:int,max:int,size:string,color:string,sort:string,page:int} */
    private static function filtersFromRequest(): array
    {
        $min = Request::int('min', 0);
        $max = Request::int('max', 0);

        return [
            'q' => trim(Request::str('q', '')),
            'cat' => trim(Request::str('cat', '')),
            'min' => $min > 0 ? $min : 0,
            'max' => $max > 0 ? $max : 0,
            'size' => trim(Request::str('size', '')),
            'color' => trim(Request::str('color', '')),
            'sort' => in_array(Request::str('sort', ''), array_keys(ProductRepository::SORTS), true)
                ? Request::str('sort', '')
                : 'recent',
            'page' => max(1, Request::int('page', 1)),
        ];
    }

    private static function card(array $p): array
    {
        $currency = SettingRepository::currency();
        $eff = (int) $p['eff_price'];
        $old = $p['promo_price'] !== null ? (int) $p['price'] : null;

        return [
            'id' => (int) $p['id'],
            'name' => (string) $p['name'],
            'slug' => (string) $p['slug'],
            'url' => Url::to('product/' . $p['slug']),
            'price' => $eff,
            'price_label' => number_format($eff, 0, ',', ' ') . ' ' . $currency,
            'old_price' => $old,
            'old_price_label' => $old !== null ? number_format($old, 0, ',', ' ') . ' ' . $currency : null,
            'on_promo' => $old !== null,
            'image' => $p['image'] !== null && $p['image'] !== ''
                ? Url::upload('products/' . $p['image'])
                : null,
            'category' => $p['category_name'] ?? null,
            'category_slug' => $p['category_slug'] ?? null,
            'stock' => (int) $p['stock'],
            'in_stock' => (int) $p['stock'] > 0,
            'is_new' => strtotime((string) $p['created_at']) > time() - 7 * 86400,
            'is_featured' => (int) $p['is_featured'] === 1,
        ];
    }
}
