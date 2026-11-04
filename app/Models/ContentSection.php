<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Textes du site, regroupés par section (site, home, missions, references, legal) au format JSON. */
final class ContentSection extends Model
{
    protected static string $table = 'content_sections';

    /** @return array<string, array> */
    public static function all(): array
    {
        $out = [];
        foreach (Database::all('SELECT section_key, data FROM content_sections') as $row) {
            $out[$row['section_key']] = json_decode($row['data'], true) ?: [];
        }
        return $out;
    }

    /** @param array<string, array> $sections */
    public static function saveAll(array $sections): void
    {
        Database::transaction(function () use ($sections) {
            foreach ($sections as $key => $data) {
                Database::exec(
                    'INSERT INTO content_sections (section_key, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)',
                    [$key, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]
                );
            }
        });
    }
}
