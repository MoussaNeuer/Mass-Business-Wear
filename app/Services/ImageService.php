<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\App;
use App\Helpers\Database;
use App\Helpers\Response;

/**
 * Service d'images produits (Sprint B) :
 *  - validation stricte (taille, MIME réel via getimagesize, dimensions)
 *  - normalisation WebP (GD) avec rotation EXIF + redimensionnement
 *  - miniature carrée (WebP léger) pour listes/admin
 *  - chemins issus de config('upload_dir') plus de chemin codé en dur
 *  - multi-fichiers : saveAll() pour l'upload drag & drop
 */
final class ImageService
{
    /** Poids max par fichier (octets). */
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** Côté max conservé sur l'image plein format. */
    public const MAX_DIM = 1600;

    /** Côté de la miniature (carrée). */
    public const THUMB_SIZE = 400;

    /** Qualité WebP. */
    private const QUALITY_FULL = 82;
    private const QUALITY_THUMB = 70;

    private const MIMES = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp',
    ];

    // ------------------------------------------------------------ Stockage

    /** Dossier absolu d'un type d'images (products / orders), créé à la demande. */
    public static function dir(string $kind = 'products'): string
    {
        $kind = $kind === 'orders' ? 'orders' : 'products';
        $root = rtrim((string) (App::config('upload_dir') ?: dirname(__DIR__, 2) . '/public/uploads'), '/');
        $dir = $root . '/' . $kind;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            Response::abort(500, 'Dossier d\'images inaccessible.');
        }
        return $dir;
    }

    /** Dossier des miniatures produits. */
    public static function thumbDir(): string
    {
        $dir = self::dir('products') . '/thumbs';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            Response::abort(500, 'Dossier de miniatures inaccessible.');
        }
        return $dir;
    }

    // ------------------------------------------------------------ Sauvegarde

    /**
     * Valide, normalise et stocke une image produit.
     * Retourne le nom de fichier unique (ex. "a1b2….webp").
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     */
    public static function saveProductImage(array $file): string
    {
        return self::save($file, 'products');
    }

    /**
     * Photo de référence jointe par un client à sa commande (Sprint C).
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     */
    public static function saveOrderImage(array $file): string
    {
        return self::save($file, 'orders');
    }

    /**
     * Pipeline commun : validation → normalisation WebP → stockage.
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     */
    private static function save(array $file, string $kind): string
    {
        self::assertUpload($file);

        $src = self::load($file['tmp_name']);
        $src = self::autoRotate($src, $file['tmp_name']);
        $full = self::resize($src, self::MAX_DIM);
        $thumb = $kind === 'products' ? self::squareThumb($src, self::THUMB_SIZE) : null;

        $name = bin2hex(random_bytes(16)) . '.webp';
        self::writeWebp($full, self::dir($kind) . '/' . $name, self::QUALITY_FULL);
        if ($thumb !== null) {
            self::writeWebp($thumb, self::thumbDir() . '/' . $name, self::QUALITY_THUMB);
        }

        imagedestroy($full);
        if ($thumb !== null) {
            imagedestroy($thumb);
        }
        if ($src !== $full) {
            imagedestroy($src);
        }

        return $name;
    }

    /**
     * @param list<array{name:string,type:string,tmp_name:string,error:int,size:int}> $files
     * @return array{saved:list<string>, errors:list<string>}
     */
    public static function saveProductImages(array $files, int $max = 8): array
    {
        $saved = [];
        $errors = [];
        foreach (array_slice($files, 0, $max) as $i => $file) {
            if (!is_array($file)) {
                continue;
            }
            try {
                $saved[] = self::saveProductImage($file);
            } catch (\RuntimeException $e) {
                $errors[] = 'Fichier ' . ($i + 1) . ' : ' . $e->getMessage();
            }
        }
        return ['saved' => $saved, 'errors' => $errors];
    }

    // ------------------------------------------------------------ Suppression

    /** Supprime l'image + sa miniature. */
    public static function delete(string $name, string $kind = 'products'): void
    {
        $name = basename($name);
        $base = self::dir($kind) . '/' . $name;
        if (is_file($base)) {
            @unlink($base);
        }
        if ($kind === 'products') {
            $thumb = self::thumbDir() . '/' . $name;
            if (is_file($thumb)) {
                @unlink($thumb);
            }
        }
    }

    // ------------------------------------------------------------ URLs

    public static function url(string $name): string
    {
        return \App\Helpers\Url::upload('products/' . basename($name));
    }

    public static function orderUrl(string $name): string
    {
        return \App\Helpers\Url::upload('orders/' . basename($name));
    }

    public static function thumbUrl(string $name): string
    {
        $name = basename($name);
        $thumb = self::thumbDir() . '/' . $name;
        if (is_file($thumb)) {
            return \App\Helpers\Url::upload('products/thumbs/' . $name);
        }
        return self::url($name);
    }

    // ------------------------------------------------------------ Validation

    /**
     * Valide un fichier téléversé SANS l'écrire (léger, sans GD).
     * Lève RuntimeException si le fichier est refusé.
     *
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     */
    public static function validate(array $file): void
    {
        self::assertUpload($file);
    }

    /** @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file */
    private static function assertUpload(array $file): void
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('Aucun fichier reçu.');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Fichier trop lourd (limite serveur).',
                UPLOAD_ERR_PARTIAL => 'Téléversement interrompu, réessayez.',
                default => 'Erreur de téléversement (code ' . $err . ').',
            });
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            throw new \RuntimeException('Fichier vide.');
        }
        if ($size > self::MAX_BYTES) {
            throw new \RuntimeException('Image trop lourde (max ' . (int) ceil(self::MAX_BYTES / 1048576) . ' Mo).');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            throw new \RuntimeException('Fichier temporaire introuvable.');
        }
        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new \RuntimeException('Fichier image invalide.');
        }
        $mime = (string) ($info['mime'] ?? '');
        if (!isset(self::MIMES[$mime])) {
            throw new \RuntimeException('Format non autorisé (JPEG, PNG ou WebP).');
        }
        $w = (int) ($info[0] ?? 0);
        $h = (int) ($info[1] ?? 0);
        if ($w < 1 || $h < 1 || $w > 8000 || $h > 8000) {
            throw new \RuntimeException('Dimensions d\'image invalides.');
        }
        // MIME déclaré vs MIME réel (finfo) : double filet anti contournement
        if (class_exists(\finfo::class)) {
            $real = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (is_string($real) && $real !== $mime) {
                throw new \RuntimeException('Contenu du fichier incohérent avec son type.');
            }
        }
    }

    // ------------------------------------------------------------ GD

    /** @return \GdImage */
    private static function load(string $path)
    {
        $info = @getimagesize($path);
        $mime = (string) ($info['mime'] ?? '');
        $fn = self::MIMES[$mime] ?? null;
        if ($fn === null || !function_exists($fn)) {
            throw new \RuntimeException('Format non pris en charge.');
        }
        $img = @$fn($path);
        if (!$img instanceof \GdImage) {
            throw new \RuntimeException('Impossible de lire l\'image (fichier corrompu ?).');
        }
        return $img;
    }

    /** Rotation auto selon la balise EXIF Orientation (photos téléphone). */
    private static function autoRotate(\GdImage $img, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if ($orientation <= 1 || $orientation > 8) {
            return $img;
        }
        $deg = match ($orientation) {
            2 => -180,
            3 => 180,
            4 => 180,
            5 => -90,
            6 => 90,
            7 => 90,
            8 => -90,
            default => 0,
        };
        if ($deg === 0) {
            return $img;
        }
        $rot = imagerotate($img, $deg, 0);
        if ($rot === false) {
            return $img;
        }
        imagedestroy($img);
        // Flip miroir pour 4/5/7
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            $w = imagesx($rot);
            $h = imagesy($rot);
            $flip = imagecreatetruecolor($w, $h);
            imagecopyresampled($flip, $rot, 0, 0, 0, 0, $w, $h, $w, $h);
            imagedestroy($rot);
            $rot = $flip;
        }
        return $rot;
    }

    /** Redimensionne en conservant le ratio, côté max $max. */
    private static function resize(\GdImage $img, int $max): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= $max && $h <= $max) {
            return $img;
        }
        $scale = min($max / $w, $max / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $out = imagecreatetruecolor($nw, $nh);
        self::alpha($out);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $out;
    }

    /** Coupure centrée en carré puis réduction à $size. */
    private static function squareThumb(\GdImage $img, int $size): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $side = min($w, $h);
        $sx = (int) (($w - $side) / 2);
        $sy = (int) (($h - $side) / 2);
        $out = imagecreatetruecolor($size, $size);
        self::alpha($out);
        imagecopyresampled($out, $img, 0, 0, $sx, $sy, $size, $size, $side, $side);
        return $out;
    }

    private static function alpha(\GdImage $img): void
    {
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $transparent);
    }

    private static function writeWebp(\GdImage $img, string $dest, int $quality): void
    {
        if (!@imagewebp($img, $dest, $quality)) {
            throw new \RuntimeException('Échec de l\'enregistrement de l\'image.');
        }
        @chmod($dest, 0644);
    }

    // ------------------------------------------------------------ DB helpers

    /** URL de la miniature pour une ligne product_images. */
    public static function thumbOf(array $image): string
    {
        return self::thumbUrl((string) ($image['file'] ?? ''));
    }

    /** Purge fichier disque + ligne DB (appartenant au produit). */
    public static function purge(int $productId, int $imageId): bool
    {
        $img = Database::one(
            'SELECT * FROM product_images WHERE id = ? AND product_id = ?',
            [$imageId, $productId]
        );
        if ($img === null) {
            return false;
        }
        self::delete((string) $img['file']);
        Database::query('DELETE FROM product_images WHERE id = ?', [$imageId]);
        return true;
    }
}
