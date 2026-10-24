<?php
namespace App\Core;

use App\Models\User;

/** Who is logged in? Keeps only the user's id in the session. */
class Auth
{
    private static ?array $user = null;

    public static function login(array $user): void
    {
        session_regenerate_id(true);       // new session id on login (prevents session fixation)
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];                    // forget the user and everything else
        session_regenerate_id(true);       // and give the browser a brand-new session id
        self::$user = null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && !empty($_SESSION['user_id'])) {
            self::$user = User::find((int) $_SESSION['user_id']);
            if (self::$user === null) unset($_SESSION['user_id']);
        }
        return self::$user;
    }

    public static function check(): bool { return self::user() !== null; }

    /** Page guard: send visitors who are not logged in to the login page. */
    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            Session::flash('error', 'Please log in to continue.');
            Url::redirect('/login');
        }
        return $user;
    }

    /** Page guard for role-specific pages (FR03). */
    public static function requireRole(string ...$roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            exit('You do not have access to this page.');
        }
        return $user;
    }
}
