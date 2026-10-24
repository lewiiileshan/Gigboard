<?php
namespace App\Core;

/** Sessions, "flash" messages and CSRF protection. */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name(Config::get('session_name'));
        session_set_cookie_params([
            'httponly' => true,          // JavaScript cannot read the cookie
            'samesite' => 'Lax',         // not sent on cross-site POSTs
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }

    /** One-time message shown on the next page (e.g. "Account created"). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): ?array
    {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }

    /** Remember what the user typed so a failed form does not wipe it. */
    public static function old(string $key, string $default = ''): string
    {
        return $_SESSION['old'][$key] ?? $default;
    }
    public static function keepOld(array $input): void { $_SESSION['old'] = $input; }
    public static function clearOld(): void            { unset($_SESSION['old']); }

    // --- CSRF: stops another website from submitting forms as the logged-in user ---
    public static function csrfToken(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function checkCsrf(): void
    {
        $sent = $_POST['_csrf'] ?? '';
        if (!is_string($sent) || !hash_equals(self::csrfToken(), $sent)) {
            http_response_code(419);
            exit('Your session expired. Go back, refresh the page and try again.');
        }
    }
}
