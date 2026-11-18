<?php
namespace App\Models;

use App\Core\Database;

/** Qualification files (certificates, CV, portfolio) attached to an application. */
class ApplicationFile
{
    public const MAX_PER_APPLICATION = 3;

    public static function create(int $applicationId, int $freelancerId, string $title, array $file): int
    {
        Database::run(
            'INSERT INTO application_files (application_id, freelancer_id, title, original_name, stored_name, mime, size_bytes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$applicationId, $freelancerId, $title, $file['original_name'], $file['stored_name'], $file['mime'], $file['size_bytes']]
        );
        return Database::lastId();
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM application_files WHERE id = ?', [$id]);
    }

    /** [applicationId => [file, file...]] for a list of application ids. */
    public static function groupedFor(array $applicationIds): array
    {
        if (!$applicationIds) return [];
        $marks = implode(',', array_fill(0, count($applicationIds), '?'));
        $out = [];
        foreach (Database::all("SELECT * FROM application_files WHERE application_id IN ($marks) ORDER BY id", array_values($applicationIds)) as $f) {
            $out[(int) $f['application_id']][] = $f;
        }
        return $out;
    }
}
