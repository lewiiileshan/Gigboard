<?php
namespace App\Models;

use App\Core\Database;

class Category
{
    public static function all(): array
    {
        return Database::all('SELECT id, name FROM categories ORDER BY name');
    }

    public static function exists(int $id): bool
    {
        return Database::one('SELECT id FROM categories WHERE id = ?', [$id]) !== null;
    }
}
