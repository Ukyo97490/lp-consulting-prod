<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ImageMeta;

/**
 * Copies WebP (GD) et dimensions des images.
 * img/photo.jpg donne img/photo.webp (même taille) et des variantes réduites pour srcset :
 * img/photo-480w.webp, img/photo-800w.webp… (seulement les largeurs inférieures à l'original).
 */
final class ImageOptimizer
{
    private const WEBP_QUALITY = 75;
    /** Largeurs des variantes WebP proposées aux navigateurs (srcset). */
    public const WIDTHS = [320, 480, 800, 1200, 1600];

    private static ?array $meta = null;

    public static function supported(): bool
    {
        return function_exists('imagewebp');
    }

    /** Chemin de la copie WebP. */
    public static function webpPath(string $path): string
    {
        return (string) preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    }

    /** Chemin d'une variante réduite : img/photo.jpg et 480 donnent img/photo-480w.webp. */
    public static function variantPath(string $path, int $width): string
    {
        return (string) preg_replace('/\.(jpe?g|png)$/i', '-' . $width . 'w.webp', $path);
    }

    /**
     * Liste srcset des copies WebP existantes (variantes réduites puis copie pleine taille).
     * @return array<int, string> largeur => chemin
     */
    public static function srcset(string $path): array
    {
        $info = self::info($path);
        $list = [];
        foreach (self::WIDTHS as $w) {
            if ($w >= $info['width']) break;
            $v = self::variantPath($path, $w);
            if ($v !== $path && is_file(PUBLIC_PATH . '/' . $v)) $list[$w] = $v;
        }
        if ($info['webp']) $list[$info['width']] = $info['webp'];
        return $list;
    }

    /** @return array{width: int, height: int, webp: ?string} webp : chemin de la copie, si elle existe */
    public static function info(string $path): array
    {
        self::$meta ??= ImageMeta::all();
        $size = self::$meta[$path] ?? null;
        if (!$size) {
            $read = @getimagesize(PUBLIC_PATH . '/' . $path);
            $size = ['width' => (int) ($read[0] ?? 0), 'height' => (int) ($read[1] ?? 0)];
            self::$meta[$path] = $size;
        }
        $webp = self::webpPath($path);
        return $size + ['webp' => $webp !== $path && is_file(PUBLIC_PATH . '/' . $webp) ? $webp : null];
    }

    /**
     * Enregistre les dimensions et crée la copie WebP et ses variantes réduites si besoin.
     * Appelé à chaque envoi d'image dans l'administration, par le bouton Maintenance et par outils/images.php.
     * @return bool true si au moins un fichier WebP a été créé
     */
    public static function process(string $path, bool $force = false): bool
    {
        $file = PUBLIC_PATH . '/' . $path;
        $read = @getimagesize($file);
        if (!$read) return false;
        [$width, $height] = [(int) $read[0], (int) $read[1]];
        ImageMeta::save($path, $width, $height);

        $isSource = in_array($read[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true);
        if (!$isSource || !self::supported()) return false;

        $targets = [self::webpPath($path) => $width];
        foreach (self::WIDTHS as $w) {
            if ($w < $width) $targets[self::variantPath($path, $w)] = $w;
        }
        $todo = array_filter($targets, fn ($t) => $force || !is_file(PUBLIC_PATH . '/' . $t) || filemtime(PUBLIC_PATH . '/' . $t) < filemtime($file), ARRAY_FILTER_USE_KEY);
        if (!$todo) return false;

        $img = $read[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($file) : @imagecreatefrompng($file);
        if (!$img) return false;
        imagepalettetotruecolor($img);
        $created = false;
        foreach ($todo as $target => $w) {
            $out = $w === $width ? $img : self::resize($img, $w, (int) round($height * $w / $width));
            imagealphablending($out, false);
            imagesavealpha($out, true);
            $abs = PUBLIC_PATH . '/' . $target;
            $ok = imagewebp($out, $abs, self::WEBP_QUALITY);
            if ($out !== $img) imagedestroy($out);
            if (!$ok) continue;
            // Une copie pleine taille plus lourde que l'original n'apporte rien.
            if ($w === $width && filesize($abs) >= filesize($file)) { unlink($abs); continue; }
            @chmod($abs, 0644);
            $created = true;
        }
        imagedestroy($img);
        return $created;
    }

    private static function resize(\GdImage $img, int $w, int $h): \GdImage
    {
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
        return $out;
    }

    /** Traite toutes les images déjà en ligne (img/ et uploads/). @return array{images: int, webp: int} */
    public static function convertAll(bool $force = false): array
    {
        $count = ['images' => 0, 'webp' => 0];
        foreach (['img', 'uploads'] as $dir) {
            foreach (glob(PUBLIC_PATH . '/' . $dir . '/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [] as $file) {
                $count['images']++;
                if (self::process($dir . '/' . basename($file), $force)) $count['webp']++;
            }
        }
        return $count;
    }
}
