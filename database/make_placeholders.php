<?php

declare(strict_types=1);

/**
 * Génère les images placeholder des produits de dev (GD, sans librairie).
 *
 * Usage : php database/make_placeholders.php
 * Sortie : public/uploads/products/p{id}-{1,2}.jpg  (800x1000, ratio 4:5)
 * Les noms de fichiers doivent correspondre à product_images (seed_dev.sql).
 */

$root = dirname(__DIR__);
$out = $root . '/public/uploads/products';
if (!is_dir($out) && !mkdir($out, 0775, true) && !is_dir($out)) {
    fwrite(STDERR, "Impossible de créer $out\n");
    exit(1);
}

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=mbw;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $products = $pdo->query("SELECT id, name FROM products WHERE status = 'active' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    fwrite(STDERR, 'Base inaccessible : ' . $e->getMessage() . "\n");
    exit(1);
}

$fontBig = 'C:/Windows/Fonts/arialbd.ttf';
$fontSmall = 'C:/Windows/Fonts/segoeui.ttf';
$hasTtf = is_file($fontBig) && is_file($fontSmall);

/** Découpe le texte en lignes d'au plus $maxChars caractères. */
$wrap = static function (string $text, int $maxChars): array {
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $current = '';
    foreach ($words as $word) {
        $try = $current === '' ? $word : $current . ' ' . $word;
        if (mb_strlen($try) > $maxChars && $current !== '') {
            $lines[] = $current;
            $current = $word;
        } else {
            $current = $try;
        }
    }
    if ($current !== '') {
        $lines[] = $current;
    }
    return $lines;
};

$written = 0;

foreach ($products as $product) {
    $id = (int) $product['id'];
    $name = (string) $product['name'];

    foreach ([1, 2] as $n) {
        $im = imagecreatetruecolor(800, 1000);

        // Dégradé vertical sombre (#0A0A0A -> #1E1E1E)
        for ($y = 0; $y < 1000; $y++) {
            $v = 10 + (int) (14 * $y / 1000);
            $c = imagecolorallocate($im, $v, $v, $v);
            imageline($im, 0, $y, 800, $y, $c);
        }

        $red = imagecolorallocate($im, 212, 0, 0);
        $grey = imagecolorallocate($im, 107, 107, 107);
        $white = imagecolorallocate($im, 255, 255, 255);

        // Accent géométrique : cercle plein selon la variante
        $accent = $n === 1
            ? imagecolorallocate($im, 90, 0, 0)
            : imagecolorallocate($im, 42, 42, 42);
        imagefilledellipse($im, $n === 1 ? 690 : 110, $n === 1 ? 150 : 870, 430, 430, $accent);

        // Filet rouge signature
        imagefilledrectangle($im, 60, 128, 180, 140, $red);

        // Marque
        if ($hasTtf) {
            imagettftext($im, 24, 0, 60, 112, $red, $fontSmall, 'MASS BUSINESS WEAR');
        } else {
            imagestring($im, 5, 60, 96, 'MASS BUSINESS WEAR', $red);
        }

        // Nom du produit centré
        $lines = $wrap($name, 18);
        if ($hasTtf) {
            $y = 470 - (count($lines) - 1) * 28;
            foreach ($lines as $line) {
                $box = imagettfbbox(40, 0, $fontBig, $line);
                $w = abs($box[2] - $box[0]);
                imagettftext($im, 40, 0, (int) ((800 - $w) / 2), $y, $white, $fontBig, $line);
                $y += 56;
            }
        } else {
            $y = 470 - (count($lines) - 1) * 12;
            foreach ($lines as $line) {
                imagestring($im, 5, (int) ((800 - strlen($line) * 6) / 2), $y, $line, $white);
                $y += 24;
            }
        }

        // Pied de page : référence + mention dev
        $footer = sprintf('RÉF. MBW-%04d  ·  PHOTO PROVISOIRE', $id);
        if ($hasTtf) {
            $box = imagettfbbox(18, 0, $fontSmall, $footer);
            $w = abs($box[2] - $box[0]);
            imagettftext($im, 18, 0, (int) ((800 - $w) / 2), 930, $grey, $fontSmall, $footer);
        } else {
            imagestring($im, 3, (int) ((800 - strlen($footer) * 6) / 2), 924, $footer, $grey);
        }

        $file = sprintf('%s/p%d-%d.jpg', $out, $id, $n);
        imagejpeg($im, $file, 84);
        imagedestroy($im);
        $written++;
    }
}

printf("%d images générées dans public/uploads/products (%d produits actifs).\n", $written, count($products));
