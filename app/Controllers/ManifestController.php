<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\BaseUrl;

/**
 * B3 : manifests PWA générés à la volée avec des chemins absolus
 * construits depuis BaseUrl (aucun chemin en dur → marche sur n'importe
 * quel domaine / sous-dossier, en local comme en production).
 */
final class ManifestController
{
    public function shop(): never
    {
        self::render([
            'name' => 'MASS BUSINESS WEAR',
            'short_name' => 'MBW',
            'description' => 'Boutique MASS BUSINESS WEAR — prêt-à-porter premium. Commandez en quelques clics sur WhatsApp.',
            'id' => self::base(),
            'start_url' => self::base(),
            'scope' => self::base(),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#0A0A0A',
            'theme_color' => '#0A0A0A',
            'lang' => 'fr',
            'dir' => 'ltr',
            'categories' => ['shopping', 'lifestyle'],
            'icons' => self::icons(),
            'shortcuts' => [
                [
                    'name' => 'Catalogue',
                    'short_name' => 'Boutique',
                    'url' => self::url('shop'),
                    'icons' => [self::icon192()],
                ],
                [
                    'name' => 'Mon panier',
                    'short_name' => 'Panier',
                    'url' => self::url('cart'),
                    'icons' => [self::icon192()],
                ],
            ],
        ]);
    }

    public function admin(): never
    {
        $base = self::url('admin/');
        self::render([
            'name' => 'MBW Admin',
            'short_name' => 'MBW Admin',
            'description' => 'Back-office MASS BUSINESS WEAR — commandes, produits, clients, stats.',
            'id' => $base,
            'start_url' => $base,
            'scope' => $base,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#0A0A0A',
            'theme_color' => '#0A0A0A',
            'lang' => 'fr',
            'dir' => 'ltr',
            'categories' => ['business', 'productivity'],
            'icons' => self::icons(),
            'shortcuts' => [
                ['name' => 'Commandes', 'short_name' => 'Cmd', 'url' => self::url('admin/orders'), 'icons' => [self::icon192()]],
                ['name' => 'Produits', 'short_name' => 'Prod', 'url' => self::url('admin/products'), 'icons' => [self::icon192()]],
                ['name' => 'Statistiques', 'short_name' => 'Stats', 'url' => self::url('admin/stats'), 'icons' => [self::icon192()]],
            ],
        ]);
    }

    /** @param array<string,mixed> $data */
    private static function render(array $data): never
    {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    private static function base(): string
    {
        return rtrim(BaseUrl::url('/'), '/');
    }

    private static function url(string $path): string
    {
        return BaseUrl::url($path);
    }

    private static function icon192(): array
    {
        return ['src' => BaseUrl::url('assets/img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'];
    }

    private static function icons(): array
    {
        return [
            ['src' => BaseUrl::url('assets/img/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => BaseUrl::url('assets/img/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => BaseUrl::url('assets/img/icon-512-maskable.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ];
    }
}
