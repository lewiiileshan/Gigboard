<?php
namespace App\Models;

use App\Core\Database;

/** A record of every admin action, so moderation can be checked later. */
class AdminLog
{
    public static function record(int $adminId, string $action, string $type, int $targetId, ?string $detail = null): void
    {
        Database::run(
            'INSERT INTO admin_actions (admin_id, action, target_type, target_id, detail) VALUES (?, ?, ?, ?, ?)',
            [$adminId, $action, $type, $targetId, $detail]
        );
    }

    public static function recent(int $limit = 8): array
    {
        return Database::all(
            'SELECT a.*, u.name AS admin_name FROM admin_actions a JOIN users u ON u.id = a.admin_id ORDER BY a.id DESC LIMIT ' . (int) $limit
        );
    }

    /** Numbers for the admin overview page. */
    public static function overview(): array
    {
        $one = fn(string $sql) => (int) (Database::one($sql)['n'] ?? 0);
        return [
            'clients'     => $one("SELECT COUNT(*) AS n FROM users WHERE role = 'client'"),
            'freelancers' => $one("SELECT COUNT(*) AS n FROM users WHERE role = 'freelancer'"),
            'suspended'   => $one('SELECT COUNT(*) AS n FROM users WHERE suspended = 1'),
            'applications'=> $one('SELECT COUNT(*) AS n FROM applications'),
            'reviews'     => $one('SELECT COUNT(*) AS n FROM reviews'),
            'disputed'    => $one("SELECT COUNT(*) AS n FROM payments WHERE status = 'disputed'"),
            'confirmed'   => (float) (Database::one("SELECT COALESCE(SUM(amount), 0) AS n FROM payments WHERE status = 'confirmed'")['n'] ?? 0),
        ];
    }
}
