<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Demandes reçues par le formulaire de contact. */
final class Message extends Model
{
    protected static string $table = 'messages';

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO messages (name, company, email, phone, mission, message, ip, user_agent) VALUES (?,?,?,?,?,?,?,?)',
            [$d['name'], $d['company'], $d['email'], $d['phone'], $d['mission'], $d['message'], $d['ip'], $d['user_agent']]
        );
    }

    public static function countRecentFromIp(string $ip, int $minutes): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > (NOW() - INTERVAL ' . (int) $minutes . ' MINUTE)', [$ip]);
    }

    public static function latest(int $limit = 500): array
    {
        return Database::all('SELECT id, name, company, email, phone, mission, message, mailed, read_at, created_at FROM messages ORDER BY id DESC LIMIT ' . max(1, $limit));
    }

    public static function unreadCount(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM messages WHERE read_at IS NULL');
    }

    public static function markRead(int $id, bool $read): void
    {
        Database::exec('UPDATE messages SET read_at = ' . ($read ? 'COALESCE(read_at, NOW())' : 'NULL') . ' WHERE id = ?', [$id]);
    }

    public static function markMailed(int $id): void
    {
        Database::exec('UPDATE messages SET mailed = 1 WHERE id = ?', [$id]);
    }

    public static function delete(int $id): bool
    {
        return Database::exec('DELETE FROM messages WHERE id = ?', [$id]) > 0;
    }
}
