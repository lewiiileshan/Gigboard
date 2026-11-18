<?php
namespace App\Models;

use App\Core\Database;

/**
 * Payment TRACKING only. GigBoard never moves money: the client pays the freelancer outside the app
 * (for example by M-Pesa) and records it here. Status path: pending -> paid (client) -> confirmed (freelancer).
 * If the freelancer says it did not arrive the status is "disputed" and the client can record it again.
 */
class Payment
{
    private const WITH_PEOPLE = 'SELECT p.*, g.title, g.status AS gig_status, g.client_id, a.freelancer_id,
            c.name AS client_name, f.name AS freelancer_name
        FROM payments p
        JOIN gigs g ON g.id = p.gig_id
        JOIN applications a ON a.gig_id = g.id AND a.status = \'accepted\'
        JOIN users c ON c.id = g.client_id
        JOIN users f ON f.id = a.freelancer_id';

    public static function find(int $id): ?array
    {
        return Database::one(self::WITH_PEOPLE . ' WHERE p.id = ?', [$id]);
    }

    public static function forGig(int $gigId): ?array
    {
        return Database::one(self::WITH_PEOPLE . ' WHERE p.gig_id = ?', [$gigId]);
    }

    /** Every payment this user takes part in (as the client or as the hired freelancer), newest first. */
    public static function forUser(int $userId): array
    {
        return Database::all(self::WITH_PEOPLE . ' WHERE g.client_id = ? OR a.freelancer_id = ? ORDER BY p.id DESC', [$userId, $userId]);
    }

    /** Each change only happens from the right starting status, so repeating a click does nothing. Returns true if it changed. */
    public static function markPaid(int $id, ?string $reference): bool
    {
        return Database::run(
            "UPDATE payments SET status = 'paid', reference = ?, paid_at = CURRENT_TIMESTAMP, confirmed_at = NULL
             WHERE id = ? AND status IN ('pending', 'disputed')",
            [$reference, $id]
        )->rowCount() === 1;
    }

    public static function confirm(int $id): bool
    {
        return Database::run("UPDATE payments SET status = 'confirmed', confirmed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'paid'", [$id])->rowCount() === 1;
    }

    public static function dispute(int $id): bool
    {
        return Database::run("UPDATE payments SET status = 'disputed' WHERE id = ? AND status = 'paid'", [$id])->rowCount() === 1;
    }

    /** Totals for the Payments page. */
    public static function summary(array $rows): array
    {
        $s = ['in_progress' => 0.0, 'due' => 0.0, 'paid' => 0.0, 'confirmed' => 0.0];
        foreach ($rows as $r) {
            $amt = (float) $r['amount'];
            if     ($r['status'] === 'confirmed')                                     $s['confirmed']  += $amt;
            elseif ($r['status'] === 'paid')                                          $s['paid']       += $amt;
            elseif ($r['gig_status'] === 'completed')                                 $s['due']        += $amt;   // pending or disputed, work approved
            else                                                                      $s['in_progress'] += $amt;
        }
        return $s;
    }
}
