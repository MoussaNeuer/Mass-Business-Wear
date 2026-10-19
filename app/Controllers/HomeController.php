<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SettingRepository;

/**
 * Accueil : hero cinématique (looks swipe), catégories, nouveautés, best-sellers, promos.
 */
final class HomeController
{
    public function index(): never
    {
        $categories = CategoryRepository::activeWithCount();
        $new = ProductRepository::homeSection('new', 8);
        $best = ProductRepository::homeSection('best', 8);
        $promo = ProductRepository::homeSection('promo', 8);

        // Dédoublonne : un produit promo peut aussi être nouveau/featured
        $best = array_values(array_filter($best, static fn (array $p): bool => !in_array(
            (int) $p['id'],
            array_map(static fn (array $n): int => (int) $n['id'], $new),
            true
        )));

        $whatsapp = SettingRepository::whatsapp();

        // Looks héro — fichiers générés dans public/assets/img/hero/
        $heroBase = dirname(__DIR__, 2) . '/public/assets/img/hero';
        $looks = [];
        $meta = [
            ['title' => 'Street Essentials', 'tag' => 'Pièces iconiques, coupe nette, attitude brute.'],
            ['title' => 'Business Edge', 'tag' => 'L’élégance streetwear au bureau — sans compromis.'],
            ['title' => 'Ensembles Signature', 'tag' => 'Looks coordonnés prêts à porter, du matin au night.'],
            ['title' => 'Drop Limité', 'tag' => 'Séries courtes. Quand c’est parti, c’est parti.'],
            ['title' => 'Premium Basics', 'tag' => 'Matières lourdes, finitions propres, durée de vie longue.'],
        ];
        for ($i = 1; $i <= 5; $i++) {
            $webp = $heroBase . '/look-' . $i . '.webp';
            $jpg = $heroBase . '/look-' . $i . '.jpg';
            if (!is_file($webp) && !is_file($jpg)) {
                continue;
            }
            $m = $meta[$i - 1] ?? ['title' => 'Look ' . $i, 'tag' => ''];
            $looks[] = [
                'n' => $i,
                'webp' => is_file($webp) ? Url::asset('img/hero/look-' . $i . '.webp') : null,
                'jpg' => is_file($jpg) ? Url::asset('img/hero/look-' . $i . '.jpg') : null,
                'title' => $m['title'],
                'tag' => $m['tag'],
            ];
        }

        View::echo('shop/home', [
            'title' => 'MASS BUSINESS WEAR — Prêt-à-porter premium',
            'categories' => $categories,
            'new' => $new,
            'best' => $best,
            'promo' => $promo,
            'whatsapp' => $whatsapp,
            'looks' => $looks,
            'shopName' => SettingRepository::get('shop_name', 'MASS BUSINESS WEAR'),
        ], 'layouts/shop');
    }
}
