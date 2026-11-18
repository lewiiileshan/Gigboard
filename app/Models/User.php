<?php
namespace App\Models;

use App\Core\Database;

/** Everything about the `users` table lives here. */
class User
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT id, name, email, phone, role, suspended, created_at FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    public static function updateContact(int $id, string $name, ?string $phone): void
    {
        Database::run('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, $phone ?: null, $id]);
    }

    public static function create(string $name, string $email, ?string $phone, string $password, string $role): int
    {
        Database::run(
            'INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)',
            [$name, strtolower($email), $phone ?: null, password_hash($password, PASSWORD_BCRYPT), $role]
        );
        return Database::lastId();
    }

    // ---------------------------------------------------------- admin helpers

    /** Search users by name or email, optionally one role. Newest first, capped. */
    public static function search(string $q, string $role, int $limit = 50): array
    {
        $where = ['1 = 1']; $params = [];
        if ($q !== '') {
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
            $where[] = "(name LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
            array_push($params, $like, $like);
        }
        if (in_array($role, ['client', 'freelancer', 'admin'], true)) { $where[] = 'role = ?'; $params[] = $role; }
        return Database::all(
            'SELECT id, name, email, role, suspended, created_at FROM users WHERE ' . implode(' AND ', $where) .
            ' ORDER BY id DESC LIMIT ' . (int) $limit, $params);
    }

    public static function setSuspended(int $id, bool $suspended): void
    {
        Database::run('UPDATE users SET suspended = ? WHERE id = ?', [$suspended ? 1 : 0, $id]);
    }

    /** Turn an existing account into an admin (used only by bin/make-admin.php). */
    public static function makeAdmin(string $email): bool
    {
        return Database::run("UPDATE users SET role = 'admin' WHERE email = ?", [strtolower($email)])->rowCount() > 0;
    }
}
