<?php
declare(strict_types=1);

namespace App\Core;

/** Accès à la configuration (config/config.php) par clés pointées : Config::get('db.host'). */
final class Config
{
    private static array $items = [];

    public static function load(string $file): void
    {
        self::$items = require $file;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) return $default;
            $value = $value[$k];
        }
        return $value;
    }
}
