<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AdminUser;
use App\Models\ContentSection;
use App\Models\Project;
use PDOException;

/** Installation : création des tables et import des contenus initiaux (sans effet si déjà fait). */
final class Installer
{
    /** @return string[] journal des opérations */
    public static function run(): array
    {
        $log = [];
        $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents(DATABASE_PATH . '/schema.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) Database::pdo()->exec($statement);
        $log[] = 'Tables créées ou déjà présentes.';

        if (ContentSection::count() === 0) {
            SiteContent::saveContent(ContentSchema::seed('content'), null, 'Contenus initiaux');
            $log[] = 'Textes initiaux importés.';
        } else {
            $log[] = 'Textes déjà présents : conservés.';
        }
        if (Project::count() === 0) {
            $n = count(SiteContent::saveProjects(ContentSchema::seed('projects'), null, 'Projets initiaux'));
            $log[] = "$n projets importés.";
        } else {
            $log[] = 'Projets déjà présents : conservés.';
        }
        return $log;
    }

    public static function adminCount(): int
    {
        try {
            return AdminUser::count();
        } catch (PDOException) {
            return 0;
        }
    }
}
