<?php
/* LP CONSULTING : fonctions d'aide utilisées dans les vues. */
declare(strict_types=1);

use App\Core\Url;
use App\Core\View;

/** Échappement HTML (toute donnée affichée passe par cette fonction). */
function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return Url::to($path);
}

function abs_url(string $path = ''): string
{
    return Url::absolute($path);
}

/** Ressource statique avec empreinte de version (cache navigateur long sans fichier périmé). */
function asset(string $path): string
{
    $source = PUBLIC_PATH . '/' . ltrim($path, '/');
    $min = (string) preg_replace('/\.(css|js)$/', '.min.$1', $source);
    // Le fichier .min n'est servi que s'il est à jour : sinon une modification de la source serait ignorée.
    if ($min !== $source && is_file($min) && (!is_file($source) || filemtime($min) >= filemtime($source))) {
        return url(substr($min, strlen(PUBLIC_PATH) + 1)) . '?v=' . filemtime($min);
    }
    return url($path) . (is_file($source) ? '?v=' . filemtime($source) : '');
}

function partial(string $name, array $data = []): string
{
    return View::capture('partials/' . $name, $data);
}

/** Texte brut -> paragraphes (une ligne vide sépare deux paragraphes). */
function paras(?string $text, string $class = ''): string
{
    $out = [];
    foreach (preg_split('/\n\s*\n/', (string) $text) as $p) {
        $p = trim($p);
        if ($p !== '') $out[] = '<p' . ($class !== '' ? ' class="' . e($class) . '"' : '') . '>' . nl2br(e($p), false) . '</p>';
    }
    return implode("\n", $out);
}

function tel_href(?string $phone): string
{
    $d = preg_replace('/\D+/', '', (string) $phone);
    if (isset($d[0]) && $d[0] === '0') $d = '262' . substr($d, 1);
    return '+' . $d;
}

/** Lien saisi dans l'administration : chemin du site, http(s), mailto ou tel uniquement. */
function safe_link(?string $href): string
{
    $href = trim((string) $href);
    if ($href === '') return '';
    if (preg_match('#^(https?://|mailto:|tel:|\#)#i', $href)) return $href;
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $href) || str_starts_with($href, '//')) return '';
    return url($href);
}

function img_url(?string $path): string
{
    return App\Services\ContentSchema::isImagePath($path) ? url((string) $path) : url('img/bandeau-chantiers.jpg');
}

/**
 * Image en <picture> : copies WebP en srcset (variantes 320 à 1600 px), dimensions réelles, chargement différé.
 * $attrs['sizes'] : largeur d'affichage pour le choix de la variante (100vw par défaut).
 * Sans fetchpriority, l'image est en loading="lazy".
 */
function picture(?string $path, string $alt, array $attrs = []): string
{
    $path = App\Services\ContentSchema::isImagePath($path) ? (string) $path : 'img/bandeau-chantiers.jpg';
    $info = App\Services\ImageOptimizer::info($path);
    $sizes = (string) ($attrs['sizes'] ?? '100vw');
    unset($attrs['sizes']);
    $attrs = ['src' => url($path), 'alt' => $alt, 'width' => $info['width'], 'height' => $info['height']] + $attrs;
    if (!isset($attrs['fetchpriority'])) $attrs += ['loading' => 'lazy'];
    $attrs += ['decoding' => 'async'];

    $html = '';
    foreach ($attrs as $name => $value) {
        if ($value !== 0) $html .= ' ' . $name . '="' . e((string) $value) . '"';
    }
    $srcset = webp_srcset($path);
    $source = $srcset !== '' ? '<source type="image/webp" srcset="' . e($srcset) . '" sizes="' . e($sizes) . '">' : '';
    return '<picture>' . $source . '<img' . $html . '></picture>';
}

/** Valeur srcset des copies WebP d'une image ("…-480w.webp 480w, …"), vide s'il n'y en a pas. */
function webp_srcset(string $path): string
{
    $list = [];
    foreach (App\Services\ImageOptimizer::srcset($path) as $w => $file) $list[] = url($file) . ' ' . $w . 'w';
    return implode(', ', $list);
}

/**
 * Précharge l'image principale de la page (LCP) : appelé par le gabarit de page, lu par la mise en page
 * qui place la balise <link rel="preload"> dans le <head>, avant la feuille de style.
 */
function preload_image(?string $path = null, string $sizes = '100vw'): ?array
{
    static $preload = null;
    if ($path !== null && App\Services\ContentSchema::isImagePath($path)) {
        $preload = ['href' => url($path), 'srcset' => webp_srcset($path), 'sizes' => $sizes];
    }
    return $preload;
}

/**
 * CSS critique à intégrer dans le <head> ('accueil' ou 'pages'), ou null s'il manque ou n'est plus à jour :
 * la feuille complète bloque alors l'affichage, comme avant.
 */
function critical_css(string $set): ?string
{
    $file = PUBLIC_PATH . '/css/critical-' . $set . '.min.css';
    $source = PUBLIC_PATH . '/css/style.css';
    if (!is_file($file) || (is_file($source) && filemtime($file) < filemtime($source))) return null;
    // Chemins relatifs à css/ rendus absolus, puisque le CSS est intégré dans la page.
    return str_replace(['url("../', '</'], ['url("' . rtrim(url(''), '/') . '/', '<\/'], (string) file_get_contents($file));
}

/** Texte alternatif d'un projet : celui saisi dans l'administration, à défaut le titre du projet. */
function project_alt(array $project): string
{
    return $project['image_alt'] !== '' ? $project['image_alt'] : $project['title'];
}

/** Texte alternatif d'une image de page : celui du projet qui l'utilise, à défaut le texte fourni. */
function image_alt(?string $path, string $fallback): string
{
    static $alts = null;
    if ($alts === null) {
        $alts = [];
        foreach (App\Services\SiteContent::projects() as $p) {
            if ($p['image_alt'] !== '') $alts[$p['image']] ??= $p['image_alt'];
        }
    }
    return $alts[$path] ?? $fallback;
}

/** Extrait propre : texte sur une ligne, coupé sur un mot entier, 155 caractères au plus. */
function excerpt(string $text, int $max = 155): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
    if (mb_strlen($text) <= $max) return $text;
    $cut = mb_substr($text, 0, $max);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space ? mb_substr($cut, 0, $space) : $cut, ' ,;:.');
}

const ICONS = [
    'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'etudes'    => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
    'prepa'     => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M7 13h4M7 17h7"/>',
    'direction' => '<path d="M2 18h20M4 18v-2a8 8 0 0 1 16 0v2"/><path d="M10 9V5h4v4M12 5V3"/>',
    'target'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/>',
    'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'phone'     => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
    'pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    'download'  => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
    'up'        => '<path d="M12 19V5M6 11l6-6 6 6"/>',
    'gear'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
];

function icon(string $name): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . (ICONS[$name] ?? '') . '</svg>';
}

function slugify(string $s, int $max = 60): string
{
    $s = trim($s);
    $s = function_exists('transliterator_transliterate')
        ? (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $s)
        : (string) @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $s));
    $s = trim(substr(trim($s, '-'), 0, $max), '-');
    return $s !== '' ? $s : 'element';
}
