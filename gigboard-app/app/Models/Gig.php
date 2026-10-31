<?php
namespace App\Models;

use App\Core\Database;

/** Everything about the `gigs` table: create, find, update, cancel and search. */
class Gig
{
    /**
     * Allowed sort orders. The visitor sends a NAME ("budget_high"); we look up the SQL here.
     * User input is never pasted into the query, so nobody can inject SQL through the sort option.
     */
    private const SORTS = [
        'newest'      => 'g.created_at DESC, g.id DESC',
        'oldest'      => 'g.created_at ASC, g.id ASC',
        'budget_high' => 'g.budget DESC, g.id DESC',
        'budget_low'  => 'g.budget ASC, g.id DESC',
    ];

    public static function sortNames(): array
    {
        return [
            'newest'      => 'Newest first',
            'oldest'      => 'Oldest first',
            'budget_high' => 'Budget: high to low',
            'budget_low'  => 'Budget: low to high',
        ];
    }

    private const SELECT = 'SELECT g.*, c.name AS category_name, u.name AS client_name
                            FROM gigs g
                            JOIN categories c ON c.id = g.category_id
                            JOIN users u      ON u.id = g.client_id';

    public static function create(int $clientId, array $d): int
    {
        Database::run(
            'INSERT INTO gigs (client_id, category_id, title, description, requirements, budget, location, deadline)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$clientId, $d['category_id'], $d['title'], $d['description'], $d['requirements'], $d['budget'], $d['location'], $d['deadline']]
        );
        return Database::lastId();
    }

    public static function find(int $id): ?array
    {
        return Database::one(self::SELECT . ' WHERE g.id = ?', [$id]);
    }

    /** Only open gigs can be edited (FR08). */
    public static function update(int $id, array $d): void
    {
        Database::run(
            "UPDATE gigs SET category_id = ?, title = ?, description = ?, requirements = ?, budget = ?, location = ?, deadline = ?
             WHERE id = ? AND status = 'open'",
            [$d['category_id'], $d['title'], $d['description'], $d['requirements'], $d['budget'], $d['location'], $d['deadline'], $id]
        );
    }

    /** Cancel an open gig. Returns false if it was not open (so nothing changed). */
    public static function cancel(int $id): bool
    {
        return Database::run("UPDATE gigs SET status = 'cancelled' WHERE id = ? AND status = 'open'", [$id])->rowCount() > 0;
    }

    /** Browse and search open gigs (FR09, FR10, FR11). Returns [rows, total]. */
    public static function search(array $f, int $page, int $perPage): array
    {
        $where  = ["g.status = 'open'"];
        $params = [];

        if ($f['q'] !== '') {
            $like = '%' . self::escapeLike($f['q']) . '%';
            $where[]  = "(g.title LIKE ? ESCAPE '!' OR g.description LIKE ? ESCAPE '!')";
            array_push($params, $like, $like);
        }
        if ($f['category'] > 0) {
            $where[]  = 'g.category_id = ?';
            $params[] = $f['category'];
        }
        if (is_numeric($f['min'])) {
            $where[]  = 'g.budget >= ?';
            $params[] = (float) $f['min'];
        }
        if (is_numeric($f['max'])) {
            $where[]  = 'g.budget <= ?';
            $params[] = (float) $f['max'];
        }
        if ($f['location'] !== '') {
            $where[]  = "g.location LIKE ? ESCAPE '!'";
            $params[] = '%' . self::escapeLike($f['location']) . '%';
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $order    = self::SORTS[$f['sort']] ?? self::SORTS['newest'];
        $offset   = max(0, ($page - 1) * $perPage);

        $total = (int) Database::one('SELECT COUNT(*) AS n FROM gigs g' . $whereSql, $params)['n'];
        // LIMIT/OFFSET are whole numbers we calculated ourselves (cast with (int)), so they are safe to write directly.
        $rows = Database::all(self::SELECT . $whereSql . ' ORDER BY ' . $order . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset, $params);

        return [$rows, $total];
    }

    public static function byClient(int $clientId): array
    {
        return Database::all(self::SELECT . ' WHERE g.client_id = ? ORDER BY g.created_at DESC, g.id DESC', [$clientId]);
    }

    /** ['open' => 2, 'completed' => 1, ...] for the dashboard. */
    public static function countsByStatus(int $clientId): array
    {
        $counts = ['open' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach (Database::all('SELECT status, COUNT(*) AS n FROM gigs WHERE client_id = ? GROUP BY status', [$clientId]) as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }
        return $counts;
    }

    public static function countOpen(): int
    {
        return (int) Database::one("SELECT COUNT(*) AS n FROM gigs WHERE status = 'open'")['n'];
    }

    /** Make % and _ in a search word mean themselves, not "match anything". */
    private static function escapeLike(string $s): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $s);
    }
}
