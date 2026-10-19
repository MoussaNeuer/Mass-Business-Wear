<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\ProductRepository;
use App\Repositories\SettingRepository;

/**
 * Fiche produit — Phase 3 : galerie swipeable, variantes croisées,
 * quantité, CTA sticky "Ajouter au panier" (localStorage).
 */
final class ProductController
{
    public function show(string $slug): never
    {
        $product = ProductRepository::findActiveBySlug($slug);
        if ($product === null) {
            Response::abort(404, 'Produit introuvable.');
        }

        $images = ProductRepository::images((int) $product['id']);
        $variants = ProductRepository::variants((int) $product['id']);
        $similar = ProductRepository::similar(
            (int) $product['id'],
            $product['category_id'] !== null ? (int) $product['category_id'] : null
        );

        // Tailles / couleurs distinctes pour l'affichage (Phase 3 : sélection complète)
        $sizes = array_values(array_unique(array_filter(array_column($variants, 'size'))));
        $colors = array_values(array_unique(array_filter(array_column($variants, 'color'))));

        $stock = (int) $product['stock'];
        $badge = match (true) {
            $stock <= 0 => 'out',
            $product['promo_price'] !== null => 'promo',
            strtotime((string) $product['created_at']) > time() - 7 * 86400 => 'new',
            default => null,
        };

        $desc = trim(strip_tags((string) $product['description']));
        if (mb_strlen($desc) > 160) {
            $desc = mb_substr($desc, 0, 157) . '…';
        }

        $effPrice = $product['promo_price'] !== null ? (int) $product['promo_price'] : (int) $product['price'];
        $mainImageForLd = isset($images[0])
            ? Url::upload('products/' . $images[0]['file'])
            : null;

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) $product['name'],
            'description' => $desc,
            'sku' => (string) ($product['slug'] ?? ''),
            'url' => Url::to('product/' . $product['slug']),
            'brand' => ['@type' => 'Brand', 'name' => 'MASS BUSINESS WEAR'],
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) $effPrice,
                'priceCurrency' => 'XOF',
                'availability' => $stock > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => Url::to('product/' . $product['slug']),
            ],
        ];
        if ($mainImageForLd !== null) {
            $jsonLd['image'] = [$mainImageForLd];
        }

        View::echo('shop/product', [
            'title' => (string) $product['name'] . ' — MASS BUSINESS WEAR',
            'meta' => $desc !== '' ? $desc : 'Commandez ' . $product['name'] . ' sur MASS BUSINESS WEAR — livraison à Dakar et partout au Sénégal.',
            'jsonLd' => $jsonLd,
            'product' => $product,
            'images' => $images,
            'variants' => $variants,
            'sizes' => $sizes,
            'colors' => $colors,
            'similar' => $similar,
            'badge' => $badge,
            'currency' => SettingRepository::currency(),
            'whatsapp' => SettingRepository::whatsapp(),
            'shareUrl' => Url::to('product/' . $product['slug']),
        ], 'layouts/shop');
    }
}
