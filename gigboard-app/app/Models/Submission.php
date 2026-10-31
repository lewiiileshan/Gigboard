<?php
namespace App\Models;

use App\Core\Database;

/** Work files delivered by the hired freelancer, and the client's decision on them. */
class Submission
{
    public static function create(int $gigId, int $freelancerId, string $note, array $file): int
    {
        Database::run(
            'INSERT INTO submissions (gig_id, freelancer_id, note, original_name, stored_name, mime, size_bytes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$gigId, $freelancerId, $note, $file['original_name'], $file['stored_name'], $file['mime'], $file['size_bytes']]
        );
        return Database::lastId();
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM submissions WHERE id = ?', [$id]);
    }

    public static function forGig(int $gigId): array
    {
        return Database::all('SELECT * FROM submissions WHERE gig_id = ? ORDER BY created_at DESC, id DESC', [$gigId]);
    }

    /** Client approves: submission approved and the gig is completed. */
    public static function approve(array $s): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $done = Database::run("UPDATE gigs SET status = 'completed' WHERE id = ? AND status = 'in_progress'", [$s['gig_id']])->rowCount();
            if ($done !== 1) { $pdo->rollBack(); return false; }
            Database::run("UPDATE submissions SET status = 'approved', reviewed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'submitted'", [$s['id']]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function requestChanges(int $id, string $feedback): void
    {
        Database::run(
            "UPDATE submissions SET status = 'changes_requested', client_feedback = ?, reviewed_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = 'submitted'",
            [$feedback, $id]
        );
    }
}
