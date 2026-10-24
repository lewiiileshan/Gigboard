<?php
namespace App\Models;

use App\Core\Database;

/** Everything about the `users` table lives here. */
class User
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    public static function create(string $name, string $email, ?string $phone, string $password, string $role): int
    {
        Database::run(
            'INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)',
            [$name, strtolower($email), $phone ?: null, password_hash($password, PASSWORD_BCRYPT), $role]
        );
        return Database::lastId();
    }
}
