<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Comptes d'administration (mots de passe hachés avec password_hash). */
final class AdminUser extends Model
{
    protected static string $table = 'admin_users';

    public static function find(int $id): ?array
    {
        return Database::one('SELECT id, email, name FROM admin_users WHERE id = ?', [$id]);
    }

    /** Inclut le hachage du mot de passe : à n'utiliser que pour l'authentification. */
    public static function findForLogin(string $email): ?array
    {
        return Database::one('SELECT id, email, password_hash FROM admin_users WHERE email = ?', [$email]);
    }

    public static function passwordHash(int $id): ?string
    {
        return Database::value('SELECT password_hash FROM admin_users WHERE id = ?', [$id]);
    }

    /** Crée le compte, ou met à jour son nom et son mot de passe s'il existe déjà. */
    public static function upsert(string $email, string $name, string $hash): void
    {
        Database::exec(
            'INSERT INTO admin_users (email, name, password_hash) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash)',
            [$email, $name, $hash]
        );
    }

    public static function updateProfile(int $id, string $name, string $email): void
    {
        Database::exec('UPDATE admin_users SET name = ?, email = ? WHERE id = ?', [$name, $email, $id]);
    }

    public static function updatePassword(int $id, string $hash): void
    {
        Database::exec('UPDATE admin_users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public static function touchLogin(int $id): void
    {
        Database::exec('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }
}
