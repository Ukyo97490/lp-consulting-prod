<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use PDOException;

/** Largeur et hauteur des images (attributs width et height, pour éviter les décalages de mise en page). */
final class ImageMeta extends Model
{
    protected static string $table = 'image_meta';

    /** @return array<string, array{width: int, height: int}> dimensions par chemin relatif à public/ */
    public static function all(): array
    {
        try {
            $out = [];
            foreach (Database::all('SELECT path, width, height FROM image_meta') as $row) {
                $out[$row['path']] = ['width' => (int) $row['width'], 'height' => (int) $row['height']];
            }
            return $out;
        } catch (PDOException) {
            return []; // Migration pas encore exécutée : les dimensions sont lues sur le fichier.
        }
    }

    public static function save(string $path, int $width, int $height): void
    {
        Database::exec(
            'INSERT INTO image_meta (path, width, height) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE width = VALUES(width), height = VALUES(height)',
            [$path, $width, $height]
        );
    }
}
