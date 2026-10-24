<?php
namespace App\Core;

/** Reads values from config/config.php, e.g. Config::get('db') */
class Config
{
    private static ?array $data = null;

    public static function get(string $key, $default = null)
    {
        self::$data ??= require dirname(__DIR__, 2) . '/config/config.php';
        return self::$data[$key] ?? $default;
    }
}
