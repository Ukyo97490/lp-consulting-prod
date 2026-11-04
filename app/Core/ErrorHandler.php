<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Erreurs PHP : journalisées, jamais affichées en production. */
final class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug): void
    {
        self::$debug = $debug;
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', STORAGE_PATH . '/logs/app.log');

        set_error_handler(function (int $no, string $msg, string $file, int $line): bool {
            if (!(error_reporting() & $no)) return false;
            throw new \ErrorException($msg, 0, $no, $file, $line);
        });
        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(Throwable $e): void
    {
        Logger::error(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $e->getMessage() . "\n");
            exit(1);
        }
        self::render($e)->send();
        exit;
    }

    public static function render(Throwable $e): Response
    {
        $detail = self::$debug ? (string) $e : null;
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            return Response::json(['error' => 'Erreur interne du serveur.', 'detail' => $detail], 500);
        }
        return Response::html(View::render('pages/error', ['detail' => $detail]), 500);
    }

    public static function debug(): bool
    {
        return self::$debug;
    }
}
