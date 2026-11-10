<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Génère css/style.min.css, css/critical.min.css et js/main.min.js à partir des sources, sans outil de build.
 * Seuls les commentaires et les espaces inutiles sont retirés : les noms de variables,
 * les chaînes et les expressions régulières ne sont pas modifiés.
 */
final class Minifier
{
    private const FILES = ['css/style.css' => 'css', 'js/main.js' => 'js'];
    private const REGEX_KEYWORDS = ['return', 'typeof', 'case', 'in', 'of', 'delete', 'void', 'throw', 'new', 'else', 'do'];

    /** @return array<string, array{before: int, after: int}> tailles en octets, par fichier généré */
    public static function run(): array
    {
        $report = [];
        foreach (self::FILES as $path => $type) {
            $source = (string) file_get_contents(PUBLIC_PATH . '/' . $path);
            $min = $type === 'css' ? self::css($source) : self::js($source);
            $target = (string) preg_replace('/\.(css|js)$/', '.min.$1', $path);
            if (file_put_contents(PUBLIC_PATH . '/' . $target, $min, LOCK_EX) === false) {
                throw new RuntimeException('Écriture impossible : ' . $target);
            }
            @chmod(PUBLIC_PATH . '/' . $target, 0644);
            $report[$target] = ['before' => strlen($source), 'after' => strlen($min)];
            if ($type === 'css') {
                foreach (array_keys(self::CRITICAL_SETS) as $set) {
                    $file = 'css/critical-' . $set . '.min.css';
                    $critical = self::critical($min, $set);
                    if (file_put_contents(PUBLIC_PATH . '/' . $file, $critical, LOCK_EX) === false) {
                        throw new RuntimeException('Écriture impossible : ' . $file);
                    }
                    @chmod(PUBLIC_PATH . '/' . $file, 0644);
                    $report[$file] = ['before' => strlen($min), 'after' => strlen($critical)];
                }
            }
        }
        return $report;
    }

    /**
     * CSS critique : règles du premier écran, intégrées dans le <head> (un jeu pour l'accueil, un pour les autres pages).
     * La feuille complète est ensuite chargée sans bloquer l'affichage ; elle réapplique ces règles à l'identique,
     * dans le même ordre.
     */
    public static function critical(string $min, string $set): string
    {
        $classes = '(?:' . self::CRITICAL_COMMON . '|' . self::CRITICAL_SETS[$set] . ')';
        $out = '';
        foreach (self::blocks($min) as [$prelude, $body]) {
            if (str_starts_with($prelude, '@font-face')) {
                $out .= $prelude . '{' . $body . '}';
            } elseif (str_starts_with($prelude, '@keyframes')) {
                if ($set === 'accueil' && str_contains($prelude, 'heroZoom')) $out .= $prelude . '{' . $body . '}';
            } elseif (str_starts_with($prelude, '@media')) {
                $inner = '';
                foreach (self::blocks($body) as [$sel, $decl]) {
                    if (self::isCritical($sel, $classes)) $inner .= $sel . '{' . $decl . '}';
                }
                if ($inner !== '') $out .= $prelude . '{' . $inner . '}';
            } elseif (self::isCritical($prelude, $classes)) {
                $out .= $prelude . '{' . $body . '}';
            }
        }
        return $out;
    }

    /** Éléments HTML de base, toujours retenus quand le sélecteur ne contient qu'eux. */
    private const CRITICAL_ELEMENTS = '(?:\*|:root|html|body|main|header|nav|section|a|img|picture|svg|h1|h2|h3|p|em|strong|ul|ol|li|button|small|span|figure)';
    /** Classes du premier écran communes à toutes les pages : en-tête, menu, boutons, barre mobile. */
    private const CRITICAL_COMMON = 'wrap|skip-link|site-header|header-inner|brand|main-nav|nav-[a-z-]+|btn[a-z-]*|eyebrow|on-dark|icon|mobile-bar[a-z-]*|reveal[a-z0-9-]*';
    /** Classes propres à chaque jeu : grand bandeau et chiffres de l'accueil ; bandeau court et début de contenu ailleurs. */
    private const CRITICAL_SETS = [
        'accueil' => 'hero[a-z-]*|stats?[a-z-]*',
        'pages'   => 'page-hero[a-z-]*|breadcrumb|stats?[a-z-]*|section[a-z-]*|split[a-z-]*|intro-text|badge[a-z-]*|ref-(?:group[a-z-]*|grid|card|media|body)|status[a-z-]*|legal',
    ];

    private static function isCritical(string $selectors, string $classes): bool
    {
        foreach (explode(',', $selectors) as $sel) {
            $sel = trim($sel);
            if (preg_match('/:(hover|focus|active|focus-visible|focus-within)/', $sel)) continue; // états d'interaction : inutiles au premier affichage
            if (preg_match('/^' . self::CRITICAL_ELEMENTS . '(?![\w-])[^.#]*$/', $sel)) return true;
            if (preg_match('/\.' . $classes . '(?![\w-])/', $sel)) return true;
        }
        return false;
    }

    /** Découpe du CSS minifié en blocs de premier niveau : [[prélude, contenu], …]. */
    private static function blocks(string $css): array
    {
        $blocks = [];
        $n = strlen($css);
        $start = 0;
        $depth = 0;
        $open = 0;
        for ($i = 0; $i < $n; $i++) {
            $c = $css[$i];
            if ($c === '"' || $c === "'") {
                $end = $i + 1;
                while ($end < $n && $css[$end] !== $c) $end += $css[$end] === '\\' ? 2 : 1;
                $i = $end;
            } elseif ($c === '{') {
                if ($depth++ === 0) $open = $i;
            } elseif ($c === '}') {
                if (--$depth === 0) {
                    $blocks[] = [trim(substr($css, $start, $open - $start)), substr($css, $open + 1, $i - $open - 1)];
                    $start = $i + 1;
                }
            }
        }
        return $blocks;
    }

    public static function css(string $css): string
    {
        $string = '"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'';
        // Commentaires retirés en une passe, pour qu'une apostrophe dans un commentaire ne soit pas prise pour une chaîne.
        $css = (string) preg_replace_callback('~/\*.*?\*/|' . $string . '~s', fn(array $m) => $m[0][0] === '/' ? '' : $m[0], $css);

        $parts = preg_split('~(' . $string . '|url\([^"\')]*\))~s', $css, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) continue; // chaînes et url() conservées telles quelles
            $part = (string) preg_replace('/\s+/', ' ', $part);
            $part = (string) preg_replace('/ ?([{};,]) ?/', '$1', $part);
            $parts[$i] = (string) preg_replace('/: /', ':', $part);
        }
        return trim(str_replace(';}', '}', implode('', $parts)));
    }

    public static function js(string $js): string
    {
        $out = '';
        $i = 0;
        $n = strlen($js);
        $space = false;
        $newline = false;

        while ($i < $n) {
            $c = $js[$i];
            $next = $js[$i + 1] ?? '';

            if ($c === '/' && $next === '/') {
                $end = strpos($js, "\n", $i);
                $i = $end === false ? $n : $end;
                continue;
            }
            if ($c === '/' && $next === '*') {
                $end = strpos($js, '*/', $i + 2);
                $i = $end === false ? $n : $end + 2;
                $space = true;
                continue;
            }
            if (ctype_space($c)) {
                if ($c === "\n" || $c === "\r") $newline = true; else $space = true;
                $i++;
                continue;
            }

            if ($out !== '') {
                if ($newline && self::keepNewline($out, $c)) $out .= "\n";
                elseif (($newline || $space) && self::keepSpace($out[-1], $c)) $out .= ' ';
            }
            $space = $newline = false;

            if ($c === '"' || $c === "'") {
                $end = self::stringEnd($js, $i);
            } elseif ($c === '`') {
                $end = self::templateEnd($js, $i);
            } elseif ($c === '/' && self::regexAllowed($out)) {
                $end = self::regexEnd($js, $i);
            } else {
                $out .= $c;
                $i++;
                continue;
            }
            $out .= substr($js, $i, $end - $i);
            $i = $end;
        }
        return $out;
    }

    /** Un retour à la ligne peut terminer une instruction : il n'est retiré que si le code ne peut pas s'arrêter là. */
    private static function keepNewline(string $out, string $next): bool
    {
        $last = $out[-1];
        if (str_contains('}])', $next) || str_contains(',;.:?', $next)) return false;
        if (str_contains('{[(,;:=*/%&|^!?<>~', $last)) return false;
        if ($last === '+' || $last === '-') return substr($out, -2) === $last . $last;
        return true;
    }

    private static function keepSpace(string $last, string $next): bool
    {
        if (self::isWord($last) && self::isWord($next)) return true;
        return $last === $next && str_contains('+-/', $last);
    }

    private static function isWord(string $c): bool
    {
        return ctype_alnum($c) || $c === '_' || $c === '$' || ord($c) > 127;
    }

    private static function regexAllowed(string $out): bool
    {
        if ($out === '') return true;
        $last = $out[-1];
        if (str_contains('(,=:[!&|?{};+-*%<>~^' . "\n", $last)) return true;
        if (!self::isWord($last)) return false;
        return preg_match('/([A-Za-z_$][\w$]*)$/', $out, $m) === 1 && in_array($m[1], self::REGEX_KEYWORDS, true);
    }

    private static function stringEnd(string $js, int $i): int
    {
        $quote = $js[$i];
        $n = strlen($js);
        for ($i++; $i < $n; $i++) {
            if ($js[$i] === '\\') $i++;
            elseif ($js[$i] === $quote) return $i + 1;
        }
        return $n;
    }

    /** Fin d'un gabarit `…${ … }…`, expressions imbriquées comprises. */
    private static function templateEnd(string $js, int $i): int
    {
        $n = strlen($js);
        $depth = 0;
        for ($i++; $i < $n; $i++) {
            $c = $js[$i];
            if ($c === '\\') {
                $i++;
            } elseif ($depth === 0) {
                if ($c === '`') return $i + 1;
                if ($c === '$' && ($js[$i + 1] ?? '') === '{') { $depth = 1; $i++; }
            } elseif ($c === '"' || $c === "'") {
                $i = self::stringEnd($js, $i) - 1;
            } elseif ($c === '`') {
                $i = self::templateEnd($js, $i) - 1;
            } elseif ($c === '{') {
                $depth++;
            } elseif ($c === '}') {
                $depth--;
            }
        }
        return $n;
    }

    private static function regexEnd(string $js, int $i): int
    {
        $n = strlen($js);
        $class = false;
        for ($i++; $i < $n; $i++) {
            $c = $js[$i];
            if ($c === '\\') {
                $i++;
            } elseif ($c === '[') {
                $class = true;
            } elseif ($c === ']') {
                $class = false;
            } elseif ($c === '/' && !$class) {
                for ($i++; $i < $n && ctype_alpha($js[$i]); $i++);
                return $i;
            }
        }
        return $n;
    }
}
