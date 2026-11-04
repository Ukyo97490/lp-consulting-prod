<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Structure des contenus et normalisation des données reçues.
 * Les textes sont ramenés à la structure de database/seeds/content.json :
 * clés inconnues ignorées, clés absentes complétées, textes nettoyés, chemins d'images vérifiés.
 */
final class ContentSchema
{
    public const SECTIONS = ['site', 'home', 'missions', 'references', 'legal'];
    public const MISSIONS = ['etudes-techniques', 'preparation-chantier', 'direction-travaux'];
    public const STATUS = ['won' => 'Offre remportée', 'lost' => 'Offre non retenue', 'works' => 'Chantier réalisé'];
    public const IMAGE_TYPES = ['visual' => 'Visuel de projet', 'photo' => 'Photo de chantier', 'none' => ''];
    public const CATEGORIES = ['etudes', 'travaux'];

    private static array $seeds = [];

    public static function seed(string $name): array
    {
        return self::$seeds[$name] ??= json_decode(
            (string) file_get_contents(DATABASE_PATH . '/seeds/' . $name . '.json'), true, 64, JSON_THROW_ON_ERROR
        );
    }

    public static function isImagePath(?string $path): bool
    {
        return (bool) preg_match('#^(img|uploads)/[A-Za-z0-9._-]+\.(jpe?g|png|webp)$#', (string) $path);
    }

    public static function cleanText(mixed $v, int $max = 20000): string
    {
        if (is_array($v) || is_object($v)) return '';
        $s = str_replace(["\r\n", "\r"], "\n", (string) $v);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
        return mb_substr(trim($s), 0, $max);
    }

    public static function content(array $data): array
    {
        return self::conform($data, self::seed('content'));
    }

    public static function project(array $p): array
    {
        $t = fn(string $k, int $max = 255) => self::cleanText($p[$k] ?? '', $max);
        $image = $t('image');
        return [
            'id'          => slugify($t('id') ?: $t('title')),
            'category'    => in_array($p['category'] ?? '', self::CATEGORIES, true) ? $p['category'] : 'travaux',
            'title'       => $t('title'),
            'client'      => $t('client'),
            'role'        => $t('role'),
            'duration'    => $t('duration', 120),
            'budget'      => $t('budget', 60),
            'budget_note' => $t('budget_note'),
            'status'      => isset(self::STATUS[$p['status'] ?? '']) ? $p['status'] : 'works',
            'image'       => self::isImagePath($image) ? $image : 'img/bandeau-chantiers.jpg',
            'image_type'  => isset(self::IMAGE_TYPES[$p['image_type'] ?? '']) ? $p['image_type'] : 'none',
            'description' => $t('description', 5000),
            'featured'    => !empty($p['featured']),
            'visible'     => !empty($p['visible']),
        ];
    }

    /** Liste de projets : normalisés, sans titre vide, identifiants uniques. */
    public static function projects(array $list): array
    {
        $out = [];
        $seen = [];
        foreach (array_slice(array_values($list), 0, 200) as $p) {
            if (!is_array($p)) continue;
            $p = self::project($p);
            if ($p['title'] === '') continue;
            $base = $p['id'];
            for ($n = 2; isset($seen[$p['id']]); $n++) $p['id'] = $base . '-' . $n;
            $seen[$p['id']] = true;
            $out[] = $p;
        }
        return $out;
    }

    private static function conform(mixed $value, mixed $template, string $key = ''): mixed
    {
        if (is_array($template) && !array_is_list($template)) {
            $value = is_array($value) ? $value : [];
            $out = [];
            foreach ($template as $k => $t) $out[$k] = self::conform($value[$k] ?? null, $t, (string) $k);
            return $out;
        }
        if (is_array($template)) {
            $value = is_array($value) ? array_values($value) : [];
            $item = $template[0] ?? '';
            $out = [];
            foreach (array_slice($value, 0, 60) as $v) {
                $c = self::conform($v, $item, $key);
                $filled = is_string($c) ? $c !== '' : implode('', array_map('strval', $c)) !== '';
                if ($filled) $out[] = $c;
            }
            return $out;
        }
        if ($value === null) return $template;
        $s = self::cleanText($value);
        if (preg_match('/(^|_)image$/', $key) && !self::isImagePath($s)) return $template;
        return $s;
    }
}
