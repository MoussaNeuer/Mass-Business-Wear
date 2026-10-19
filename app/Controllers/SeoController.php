<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\BaseUrl;
use App\Helpers\Database;
use App\Helpers\Response;

/** SEO Phase 7 : robots.txt + sitemap.xml dynamiques. */
final class SeoController
{
    public function robots(): never
    {
        $base = rtrim(BaseUrl::url('/'), '/');
        $body = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admin\n"
            . "Disallow: /api/\n"
            . "Disallow: /cart\n"
            . "Disallow: /checkout\n"
            . "Disallow: /order/\n\n"
            . "Sitemap: {$base}/sitemap.xml\n";
        header('Content-Type: text/plain; charset=utf-8');
        echo $body;
        exit;
    }

    public function sitemap(): never
    {
        $base = rtrim(BaseUrl::url('/'), '/');
        $urls = [];

        // Pages statiques
        foreach (['', 'shop', 'contact'] as $p) {
            $urls[] = ['loc' => $base . '/' . ltrim($p, '/'), 'changefreq' => 'daily', 'priority' => $p === '' ? '1.0' : '0.7'];
        }

        // Catégories actives
        try {
            $cats = Database::all("SELECT slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
            foreach ($cats as $c) {
                $urls[] = ['loc' => $base . '/category/' . $c['slug'], 'changefreq' => 'daily', 'priority' => '0.8'];
            }

            // Produits actifs
            $prods = Database::all(
                "SELECT slug, updated_at FROM products WHERE status = 'active' ORDER BY updated_at DESC LIMIT 500"
            );
            foreach ($prods as $p) {
                $urls[] = [
                    'loc' => $base . '/product/' . $p['slug'],
                    'lastmod' => !empty($p['updated_at']) ? substr((string) $p['updated_at'], 0, 10) : null,
                    'changefreq' => 'weekly',
                    'priority' => '0.9',
                ];
            }
        } catch (\Throwable) {
            // DB indisponible : sitemap minimal
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . self::x($u['loc']) . '</loc>';
            if (!empty($u['lastmod'])) {
                $xml .= '<lastmod>' . self::x((string) $u['lastmod']) . '</lastmod>';
            }
            $xml .= '<changefreq>' . $u['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $u['priority'] . '</priority>';
            $xml .= "</url>\n";
        }
        $xml .= '</urlset>';

        header('Content-Type: application/xml; charset=utf-8');
        echo $xml;
        exit;
    }

    private static function x(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
