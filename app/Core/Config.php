<?php
namespace App\Core;

/** Reads values from config/config.php (and config/config.local.php if present), e.g. Config::get('db') */
class Config
{
    private static ?array $data = null;

    public static function get(string $key, $default = null)
    {
        if (self::$data === null) {
            $dir = dirname(__DIR__, 2) . '/config';
            self::$data = require $dir . '/config.php';
            // Optional config/config.local.php overrides the defaults and is never committed to git.
            if (is_file($dir . '/config.local.php')) {
                self::$data = array_replace_recursive(self::$data, require $dir . '/config.local.php');
            }
        }
        return self::$data[$key] ?? $default;
    }
}
