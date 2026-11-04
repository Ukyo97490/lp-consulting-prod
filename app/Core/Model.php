<?php
declare(strict_types=1);

namespace App\Core;

/** Modèle de base : chaque modèle encapsule l'accès SQL à une table. */
abstract class Model
{
    protected static string $table = '';

    public static function count(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM ' . static::$table);
    }
}
