<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Projets / références. L'identifiant public d'un projet est son « slug ». */
final class Project extends Model
{
    protected static string $table = 'projects';

    public static function all(bool $onlyVisible = false): array
    {
        $rows = Database::all('SELECT * FROM projects' . ($onlyVisible ? ' WHERE visible = 1' : '') . ' ORDER BY position, id');
        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * Remplace la liste complète (déjà normalisée) : l'ordre reçu devient l'ordre d'affichage,
     * les projets absents sont supprimés.
     */
    public static function replaceAll(array $projects): void
    {
        Database::transaction(function () use ($projects) {
            $slugs = array_column($projects, 'id');
            if ($slugs) {
                Database::exec('DELETE FROM projects WHERE slug NOT IN (' . implode(',', array_fill(0, count($slugs), '?')) . ')', $slugs);
            } else {
                Database::exec('DELETE FROM projects');
            }
            foreach ($projects as $i => $p) {
                Database::exec(
                    'INSERT INTO projects (slug, category, title, client, role, duration, budget, budget_note, status, image, image_type, description, featured, visible, position)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE category=VALUES(category), title=VALUES(title), client=VALUES(client), role=VALUES(role), duration=VALUES(duration),
                       budget=VALUES(budget), budget_note=VALUES(budget_note), status=VALUES(status), image=VALUES(image), image_type=VALUES(image_type),
                       description=VALUES(description), featured=VALUES(featured), visible=VALUES(visible), position=VALUES(position)',
                    [$p['id'], $p['category'], $p['title'], $p['client'], $p['role'], $p['duration'], $p['budget'], $p['budget_note'],
                     $p['status'], $p['image'], $p['image_type'], $p['description'], (int) $p['featured'], (int) $p['visible'], $i]
                );
            }
        });
    }

    private static function hydrate(array $r): array
    {
        return [
            'id' => $r['slug'], 'category' => $r['category'], 'title' => $r['title'], 'client' => $r['client'], 'role' => $r['role'],
            'duration' => $r['duration'], 'budget' => $r['budget'], 'budget_note' => $r['budget_note'], 'status' => $r['status'],
            'image' => $r['image'], 'image_type' => $r['image_type'], 'description' => $r['description'],
            'featured' => (bool) $r['featured'], 'visible' => (bool) $r['visible'],
        ];
    }
}
