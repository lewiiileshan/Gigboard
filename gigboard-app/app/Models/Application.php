<?php
namespace App\Models;

use App\Core\Database;

/** Freelancers' applications for gigs, and hiring (accepting one application). */
class Application
{
    public static function create(int $gigId, int $freelancerId, string $proposal, float $price): int
    {
        Database::run(
            'INSERT INTO applications (gig_id, freelancer_id, proposal, proposed_price) VALUES (?, ?, ?, ?)',
            [$gigId, $freelancerId, $proposal, $price]
        );
        return Database::lastId();
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM applications WHERE id = ?', [$id]);
    }

    /** Has this freelancer already applied to this gig? */
    public static function mineFor(int $gigId, int $freelancerId): ?array
    {
        return Database::one('SELECT * FROM applications WHERE gig_id = ? AND freelancer_id = ?', [$gigId, $freelancerId]);
    }

    /** All applicants for a gig (for the client), newest first. */
    public static function forGig(int $gigId): array
    {
        return Database::all(
            'SELECT a.*, u.name AS freelancer_name FROM applications a
             JOIN users u ON u.id = a.freelancer_id
             WHERE a.gig_id = ? ORDER BY a.created_at DESC, a.id DESC',
            [$gigId]
        );
    }

    /** The hired freelancer's accepted application, or null. */
    public static function accepted(int $gigId): ?array
    {
        return Database::one(
            "SELECT a.*, u.name AS freelancer_name FROM applications a
             JOIN users u ON u.id = a.freelancer_id
             WHERE a.gig_id = ? AND a.status = 'accepted'",
            [$gigId]
        );
    }

    /** A freelancer's own applications with the gig details. */
    public static function byFreelancer(int $freelancerId): array
    {
        return Database::all(
            'SELECT a.*, g.title, g.status AS gig_status, g.budget, u.name AS client_name
             FROM applications a
             JOIN gigs g  ON g.id = a.gig_id
             JOIN users u ON u.id = g.client_id
             WHERE a.freelancer_id = ? ORDER BY a.created_at DESC, a.id DESC',
            [$freelancerId]
        );
    }

    /**
     * Hire: accept this application, reject the rest, move the gig to "in progress" and
     * create the payment record. All-or-nothing (a transaction). Returns false if the gig was no longer open.
     */
    public static function hire(array $app): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $moved = Database::run("UPDATE gigs SET status = 'in_progress' WHERE id = ? AND status = 'open'", [$app['gig_id']])->rowCount();
            if ($moved !== 1) { $pdo->rollBack(); return false; }

            Database::run("UPDATE applications SET status = 'accepted' WHERE id = ?", [$app['id']]);
            Database::run("UPDATE applications SET status = 'rejected' WHERE gig_id = ? AND id <> ? AND status = 'pending'", [$app['gig_id'], $app['id']]);
            Database::run('INSERT INTO payments (gig_id, amount) VALUES (?, ?)', [$app['gig_id'], $app['proposed_price']]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
