<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\AdminUser;
use App\Models\LoginAttempt;
use InvalidArgumentException;

/** Authentification de l'administration. */
final class Auth
{
    public const MAX_FAILS = 5;       // échecs autorisés…
    public const WINDOW_MIN = 15;     // …sur cette durée (minutes), par adresse IP

    // Hachage factice : la vérification prend le même temps qu'un compte existe ou non.
    private const DUMMY_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    private static array|false|null $user = null;

    public static function user(): ?array
    {
        if (self::$user === null) {
            $id = (int) Session::get('uid', 0);
            self::$user = $id ? (AdminUser::find($id) ?: false) : false;
        }
        return self::$user ?: null;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['id'] : null;
    }

    /** @return string|null message d'erreur, null si la connexion a réussi */
    public static function attempt(string $email, string $password, string $ip): ?string
    {
        if (LoginAttempt::recentFailures($ip, self::WINDOW_MIN) >= self::MAX_FAILS) {
            return 'Trop de tentatives. Réessayez dans ' . self::WINDOW_MIN . ' minutes.';
        }
        $email = mb_strtolower(trim($email));
        $account = AdminUser::findForLogin($email);
        $ok = password_verify($password, $account['password_hash'] ?? self::DUMMY_HASH) && $account !== null;
        LoginAttempt::record($ip, $email, $ok);
        LoginAttempt::purgeOlderThan(30);
        if (!$ok) return 'Identifiants incorrects.';

        if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
            AdminUser::updatePassword((int) $account['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        Session::regenerate();
        Session::set('uid', (int) $account['id']);
        AdminUser::touchLogin((int) $account['id']);
        self::$user = null;
        return null;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = false;
    }

    /**
     * Le nom se change librement. L'e-mail sert à la connexion : le changer exige le mot de passe actuel.
     * @return string|null message d'erreur, null si le profil a été mis à jour
     */
    public static function updateProfile(int $userId, string $name, string $email, string $current): ?string
    {
        $name = trim($name);
        $email = mb_strtolower(trim($email));
        if ($name === '' || mb_strlen($name) > 120) return 'Le nom doit contenir entre 1 et 120 caractères.';
        if (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Adresse e-mail invalide.';

        $emailChanged = $email !== (AdminUser::find($userId)['email'] ?? null);
        if ($emailChanged) {
            $hash = AdminUser::passwordHash($userId);
            if (!$hash || !password_verify($current, $hash)) return 'Mot de passe actuel incorrect.';
            if (AdminUser::findForLogin($email)) return 'Cette adresse est déjà utilisée par un autre compte.';
        }
        AdminUser::updateProfile($userId, $name, $email);
        if ($emailChanged) Session::regenerate();
        self::$user = null;
        return null;
    }

    /** @return string|null message d'erreur, null si le mot de passe a été changé */
    public static function changePassword(int $userId, string $current, string $new): ?string
    {
        $hash = AdminUser::passwordHash($userId);
        if (!$hash || !password_verify($current, $hash)) return 'Mot de passe actuel incorrect.';
        if ($err = self::passwordProblem($new)) return $err;
        AdminUser::updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));
        Session::regenerate();
        return null;
    }

    public static function passwordProblem(string $pw): ?string
    {
        if (mb_strlen($pw) < 12) return 'Le mot de passe doit contenir au moins 12 caractères.';
        if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return 'Le mot de passe doit contenir des lettres et des chiffres.';
        return null;
    }

    public static function createAdmin(string $email, string $name, string $password): void
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Adresse e-mail invalide.');
        if ($err = self::passwordProblem($password)) throw new InvalidArgumentException($err);
        AdminUser::upsert($email, trim($name), password_hash($password, PASSWORD_DEFAULT));
    }
}
