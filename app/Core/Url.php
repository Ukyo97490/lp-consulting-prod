<?php
declare(strict_types=1);

namespace App\Core;

/** Construction des URL (site installé à la racine du domaine ou dans un sous-dossier). */
final class Url
{
    private static ?string $base = null;

    public static function detectBase(string $uri): string
    {
        $cfg = Config::get('app.base_path');
        if ($cfg !== null) return self::$base = rtrim((string) $cfg, '/');
        $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        // Racine du domaine sur le dossier du projet (réécriture vers public/ par le .htaccess racine).
        if (str_ends_with($b, '/public') && !str_starts_with($uri, $b . '/') && $uri !== $b) $b = substr($b, 0, -7);
        return self::$base = $b;
    }

    public static function base(): string
    {
        return self::$base ?? rtrim((string) Config::get('app.base_path', ''), '/');
    }

    public static function to(string $path = ''): string
    {
        return self::base() . '/' . ltrim($path, '/');
    }

    public static function absolute(string $path = ''): string
    {
        return rtrim((string) Config::get('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
