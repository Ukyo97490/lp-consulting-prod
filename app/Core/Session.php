<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session de l'administration uniquement (cookie limité à /admin) :
 * les pages publiques ne déposent aucun cookie.
 */
final class Session
{
    private const IDLE = 7200; // déconnexion après 2 h d'inactivité

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_name('lp_admin');
        session_set_cookie_params(['lifetime' => 0, 'path' => Url::to('admin'), 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        if (isset($_SESSION['_seen']) && time() - $_SESSION['_seen'] > self::IDLE) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_seen'] = time();
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /** Nouvel identifiant de session et nouveau jeton CSRF (connexion, changement de mot de passe). */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::start();
        return $_SESSION['_csrf'];
    }

    public static function csrfValid(?string $token): bool
    {
        return is_string($token) && hash_equals(self::csrfToken(), $token);
    }
}
