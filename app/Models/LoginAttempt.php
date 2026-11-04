<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Tentatives de connexion à l'administration (limitation des essais par adresse IP). */
final class LoginAttempt extends Model
{
    protected static string $table = 'login_attempts';

    public static function record(string $ip, string $email, bool $success): void
    {
        Database::exec('INSERT INTO login_attempts (ip, email, success) VALUES (?, ?, ?)', [$ip, mb_substr($email, 0, 190), $success ? 1 : 0]);
    }

    public static function recentFailures(string $ip, int $minutes): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND attempted_at > (NOW() - INTERVAL ' . (int) $minutes . ' MINUTE)',
            [$ip]
        );
    }

    public static function purgeOlderThan(int $days): void
    {
        Database::exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL ' . (int) $days . ' DAY)');
    }
}
