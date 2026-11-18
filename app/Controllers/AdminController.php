<?php
namespace App\Controllers;

use App\Core\{Auth, Session, Url, View};
use App\Models\{AdminLog, Gig, User};

/**
 * Admin moderation (step 9): a platform overview, suspending or restoring accounts and
 * removing open gigs. Every change is written to the admin_actions log.
 * Admins are made with bin/make-admin.php, never through the register form.
 */
class AdminController
{
    /** GET /admin */
    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('admin/index', [
            'title' => 'Admin overview', 'nav' => 'admin',
            'stats' => AdminLog::overview(), 'gigs' => Gig::platformCounts(), 'log' => AdminLog::recent(),
        ]);
    }

    /** GET /admin/users */
    public function users(): void
    {
        Auth::requireRole('admin');
        $q    = $this->text($_GET, 'q');
        $role = $this->text($_GET, 'role');
        View::render('admin/users', [
            'title' => 'Users', 'nav' => 'admin-users', 'q' => $q, 'role' => $role, 'users' => User::search($q, $role),
        ]);
    }

    /** POST /admin/users/{id}/suspend */
    public function suspend(int $id): void
    {
        $admin  = Auth::requireRole('admin');
        $target = $this->targetUser($id);
        Session::checkCsrf();

        User::setSuspended($id, true);
        AdminLog::record((int) $admin['id'], 'suspend_user', 'user', $id, $target['email']);
        Session::flash('success', $target['name'] . ' has been suspended.');
        Url::redirect('/admin/users');
    }

    /** POST /admin/users/{id}/restore */
    public function restore(int $id): void
    {
        $admin  = Auth::requireRole('admin');
        $target = $this->targetUser($id);
        Session::checkCsrf();

        User::setSuspended($id, false);
        AdminLog::record((int) $admin['id'], 'restore_user', 'user', $id, $target['email']);
        Session::flash('success', $target['name'] . ' can use GigBoard again.');
        Url::redirect('/admin/users');
    }

    /** GET /admin/gigs */
    public function gigs(): void
    {
        Auth::requireRole('admin');
        $q      = $this->text($_GET, 'q');
        $status = $this->text($_GET, 'status');
        View::render('admin/gigs', [
            'title' => 'Gigs', 'nav' => 'admin-gigs', 'q' => $q, 'status' => $status, 'gigs' => Gig::adminSearch($q, $status),
        ]);
    }

    /** POST /admin/gigs/{id}/remove */
    public function removeGig(int $id): void
    {
        $admin = Auth::requireRole('admin');
        $gig   = Gig::find($id) ?? abort(404, 'Gig not found');
        Session::checkCsrf();

        $reason = $this->text($_POST, 'reason');
        if ($gig['status'] !== 'open') {
            Session::flash('error', 'Only open gigs can be removed. Work that has started is not touched.');
        } elseif (mb_strlen($reason) < 5 || mb_strlen($reason) > 255) {
            Session::flash('error', 'Give a reason of 5 to 255 characters. The client will see it.');
        } elseif (Gig::adminRemove($id, $reason)) {
            AdminLog::record((int) $admin['id'], 'remove_gig', 'gig', $id, $reason);
            Session::flash('success', 'Gig removed.');
        }
        Url::redirect('/admin/gigs');
    }

    // ------------------------------------------------------------------ helpers

    /** The account must exist, and admins (including yourself) cannot be suspended here. */
    private function targetUser(int $id): array
    {
        $target = User::find($id) ?? abort(404, 'User not found');
        if ($target['role'] === 'admin') abort(403, 'Admin accounts cannot be changed here.');
        return $target;
    }

    private function text(array $source, string $key): string
    {
        $v = $source[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }
}
