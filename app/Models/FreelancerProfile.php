<?php
namespace App\Models;

use App\Core\Database;

/** The extra details only freelancers have: headline, town, bio, skills, rate, portfolio link. */
class FreelancerProfile
{
    public static function find(int $userId): ?array
    {
        return Database::one('SELECT * FROM freelancer_profiles WHERE user_id = ?', [$userId]);
    }

    /** Create or update (checked first, so it works the same on MySQL and SQLite). */
    public static function save(int $userId, array $d): void
    {
        $vals = [$d['headline'], $d['town'], $d['bio'], $d['skills'], $d['hourly_rate'], $d['portfolio_url']];
        if (self::find($userId)) {
            Database::run('UPDATE freelancer_profiles SET headline = ?, town = ?, bio = ?, skills = ?, hourly_rate = ?, portfolio_url = ? WHERE user_id = ?', [...$vals, $userId]);
        } else {
            Database::run('INSERT INTO freelancer_profiles (headline, town, bio, skills, hourly_rate, portfolio_url, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)', [...$vals, $userId]);
        }
    }

    /** Profiles for a list of user ids: [userId => profile]. */
    public static function forUsers(array $userIds): array
    {
        if (!$userIds) return [];
        $marks = implode(',', array_fill(0, count($userIds), '?'));
        $out = [];
        foreach (Database::all("SELECT * FROM freelancer_profiles WHERE user_id IN ($marks)", array_values($userIds)) as $p) {
            $out[(int) $p['user_id']] = $p;
        }
        return $out;
    }

    /** "Logo design, Branding" -> ['Logo design', 'Branding'] */
    public static function skillList(?string $skills): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $skills)), fn($s) => $s !== ''));
    }
}
