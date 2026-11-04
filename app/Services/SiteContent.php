<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ContentSection;
use App\Models\Project;
use App\Models\Revision;

/**
 * Lecture et publication des contenus du site.
 * Chaque enregistrement est publié immédiatement et ajouté à l'historique (restauration possible).
 */
final class SiteContent
{
    public const MAX_REVISIONS = 100;

    private static ?array $content = null;

    public static function content(): array
    {
        return self::$content ??= ContentSchema::content(ContentSection::all());
    }

    public static function projects(bool $onlyVisible = false): array
    {
        return Project::all($onlyVisible);
    }

    public static function saveContent(array $content, ?int $userId, string $label = 'Textes modifiés'): array
    {
        $content = ContentSchema::content($content);
        Database::transaction(function () use ($content, $userId, $label) {
            ContentSection::saveAll(array_intersect_key($content, array_flip(ContentSchema::SECTIONS)));
            self::$content = $content;
            self::snapshot($label, $userId);
        });
        return $content;
    }

    public static function saveProjects(array $projects, ?int $userId, string $label = 'Projets modifiés'): array
    {
        $projects = ContentSchema::projects($projects);
        Database::transaction(function () use ($projects, $userId, $label) {
            Project::replaceAll($projects);
            self::snapshot($label, $userId);
        });
        return $projects;
    }

    /** Republie une version de l'historique (la version actuelle reste dans l'historique). */
    public static function restore(int $revisionId, ?int $userId): bool
    {
        $rev = Revision::find($revisionId);
        if (!$rev || !isset($rev['snapshot']['content'], $rev['snapshot']['projects'])) return false;
        $label = 'Restauration de la version du ' . date('d/m/Y H:i', strtotime($rev['created_at']));
        Database::transaction(function () use ($rev, $userId, $label) {
            $content = ContentSchema::content($rev['snapshot']['content']);
            ContentSection::saveAll(array_intersect_key($content, array_flip(ContentSchema::SECTIONS)));
            self::$content = $content;
            Project::replaceAll(ContentSchema::projects($rev['snapshot']['projects']));
            self::snapshot($label, $userId);
        });
        return true;
    }

    private static function snapshot(string $label, ?int $userId): void
    {
        Revision::create($label, ['content' => self::content(), 'projects' => Project::all()], $userId);
        Revision::prune(self::MAX_REVISIONS);
    }
}
