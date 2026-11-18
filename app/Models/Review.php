<?php
namespace App\Models;

use App\Core\Database;

/** Two-way reviews: after a gig is completed the client and the hired freelancer each rate the other once. */
class Review
{
    /** Both reviews of one gig, with the writer's name. */
    public static function forGig(int $gigId): array
    {
        return Database::all(
            'SELECT r.*, u.name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.gig_id = ? ORDER BY r.id',
            [$gigId]
        );
    }

    public static function exists(int $gigId, int $reviewerId): bool
    {
        return Database::one('SELECT id FROM reviews WHERE gig_id = ? AND reviewer_id = ?', [$gigId, $reviewerId]) !== null;
    }

    /** Returns false (and saves nothing) if this person already reviewed the gig. */
    public static function create(int $gigId, int $reviewerId, int $revieweeId, int $rating, ?string $comment): bool
    {
        if (self::exists($gigId, $reviewerId)) return false;
        Database::run(
            'INSERT INTO reviews (gig_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)',
            [$gigId, $reviewerId, $revieweeId, $rating, $comment]
        );
        return true;
    }

    /** Average and number of reviews received by a user. */
    public static function summary(int $userId): array
    {
        $r = Database::one('SELECT COUNT(*) AS n, AVG(rating) AS avg FROM reviews WHERE reviewee_id = ?', [$userId]);
        return ['count' => (int) ($r['n'] ?? 0), 'avg' => $r && $r['n'] ? round((float) $r['avg'], 1) : null];
    }

    /** Latest reviews a user received, with the gig title and writer. */
    public static function received(int $userId, int $limit = 10): array
    {
        return Database::all(
            'SELECT r.*, u.name AS reviewer_name, g.title AS gig_title
             FROM reviews r JOIN users u ON u.id = r.reviewer_id JOIN gigs g ON g.id = r.gig_id
             WHERE r.reviewee_id = ? ORDER BY r.id DESC LIMIT ' . (int) $limit,
            [$userId]
        );
    }
}
