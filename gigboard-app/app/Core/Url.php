<?php
namespace App\Core;

/** Builds links that work whether the site is at / or inside a sub-folder. */
class Url
{
    /** Folder the site lives in, e.g. "/gigboard/public" or "" at the root. */
    public static function base(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (!str_ends_with($script, '/index.php')) return '';       // not running through our front controller
        $dir = dirname($script);
        return $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');
    }

    public static function to(string $path = '/'): string
    {
        return self::base() . '/' . ltrim($path, '/');
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::to($path));
        exit;
    }
}
