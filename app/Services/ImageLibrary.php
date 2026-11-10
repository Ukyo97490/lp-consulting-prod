<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;

/** Images disponibles (img/ et uploads/) et enregistrement des images envoyées depuis l'administration. */
final class ImageLibrary
{
    private const MAX_BYTES = 10 * 1024 * 1024;
    private const MAX_PIXELS = 40_000_000;
    private const MAX_SIDE = 1600;

    /** @return string[] chemins relatifs à public/ */
    public static function all(): array
    {
        $out = [];
        foreach (['img', 'uploads'] as $dir) {
            foreach (glob(PUBLIC_PATH . '/' . $dir . '/*') ?: [] as $file) {
                $name = basename($file);
                if ($dir === 'img' && preg_match('/^(logo|og-image)/', $name)) continue;
                if (str_ends_with($name, '.webp') && glob(substr($file, 0, -5) . '.{jpg,jpeg,png}', GLOB_BRACE)) continue; // copie WebP d'une image de la liste
                if (preg_match('/^(.+)-\d+w\.webp$/', $name, $m) && glob(dirname($file) . '/' . $m[1] . '.{jpg,jpeg,png}', GLOB_BRACE)) continue; // variante réduite (srcset)
                if (ContentSchema::isImagePath($dir . '/' . $name)) $out[] = $dir . '/' . $name;
            }
        }
        return $out;
    }

    /**
     * Vérifie le type réel du fichier, puis le redimensionne et le réencode avec GD
     * (suppression des métadonnées et de tout contenu caché).
     * @return string chemin relatif à public/
     */
    public static function store(?array $f): string
    {
        if (!$f || !isset($f['error']) || is_array($f['error'])) throw new HttpException(422, 'Aucun fichier reçu.');
        if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $f['size'] > self::MAX_BYTES) {
            throw new HttpException(422, 'Image trop lourde (10 Mo maximum).');
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) throw new HttpException(422, 'Envoi interrompu, merci de réessayer.');

        $info = @getimagesize($f['tmp_name']);
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($types[$info[2]])) throw new HttpException(422, 'Format non accepté : JPG, PNG ou WebP.');
        if ($info[0] * $info[1] > self::MAX_PIXELS) throw new HttpException(422, 'Image trop grande (40 mégapixels maximum).');

        $dir = PUBLIC_PATH . '/uploads';
        $name = slugify(pathinfo((string) $f['name'], PATHINFO_FILENAME), 50) . '-' . bin2hex(random_bytes(3));

        if (extension_loaded('gd')) {
            $file = $name . '.jpg';
            self::reencode($f['tmp_name'], $info[2], $dir . '/' . $file);
        } else {
            $file = $name . '.' . $types[$info[2]];
            if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $file)) throw new HttpException(500, 'Enregistrement impossible (droits du dossier uploads).');
        }
        @chmod($dir . '/' . $file, 0644);
        return 'uploads/' . $file;
    }

    private static function reencode(string $tmp, int $type, string $target): void
    {
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
            IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
            IMAGETYPE_WEBP => @imagecreatefromwebp($tmp),
        };
        if (!$src) throw new HttpException(422, 'Image illisible.');
        if ($type === IMAGETYPE_JPEG) $src = self::orient($src, $tmp);

        $w = imagesx($src);
        $h = imagesy($src);
        $r = min(1, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $r));
        $nh = max(1, (int) round($h * $r));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imageinterlace($dst, true);
        $ok = imagejpeg($dst, $target, 82);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) throw new HttpException(500, 'Enregistrement impossible (droits du dossier uploads).');
    }

    /** Applique l'orientation EXIF des photos prises au téléphone. */
    private static function orient(\GdImage $img, string $file): \GdImage
    {
        if (!function_exists('exif_read_data')) return $img;
        $angle = [3 => 180, 6 => -90, 8 => 90][(int) (@exif_read_data($file)['Orientation'] ?? 1)] ?? 0;
        if ($angle && ($rotated = imagerotate($img, $angle, 0))) {
            imagedestroy($img);
            return $rotated;
        }
        return $img;
    }
}
