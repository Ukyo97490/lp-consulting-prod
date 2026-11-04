<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Historique : instantané complet (textes + projets) enregistré après chaque modification. */
final class Revision extends Model
{
    protected static string $table = 'revisions';

    public static function create(string $label, array $snapshot, ?int $userId): int
    {
        return Database::insert(
            'INSERT INTO revisions (label, snapshot, user_id) VALUES (?, ?, ?)',
            [mb_substr($label, 0, 120), json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $userId]
        );
    }

    public static function latest(int $limit = 100): array
    {
        return Database::all(
            'SELECT r.id, r.label, r.created_at, u.name, u.email FROM revisions r LEFT JOIN admin_users u ON u.id = r.user_id ORDER BY r.id DESC LIMIT ' . max(1, $limit)
        );
    }

    public static function find(int $id): ?array
    {
        $row = Database::one('SELECT id, label, snapshot, created_at FROM revisions WHERE id = ?', [$id]);
        if (!$row) return null;
        $row['snapshot'] = json_decode($row['snapshot'], true);
        return is_array($row['snapshot']) ? $row : null;
    }

    /** Ne conserve que les $keep dernières versions. */
    public static function prune(int $keep): void
    {
        $cut = Database::value('SELECT id FROM revisions ORDER BY id DESC LIMIT 1 OFFSET ' . max(1, $keep));
        if ($cut) Database::exec('DELETE FROM revisions WHERE id <= ?', [$cut]);
    }

    public static function lastDate(): ?string
    {
        return Database::value('SELECT MAX(created_at) FROM revisions');
    }
}
