<?php
declare(strict_types=1);

namespace App\Core;

/** Journal des erreurs : storage/logs/app.log */
final class Logger
{
    public static function error(string $message): void
    {
        @error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", 3, STORAGE_PATH . '/logs/app.log');
    }
}
