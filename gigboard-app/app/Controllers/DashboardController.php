<?php
namespace App\Controllers;

use App\Core\{Auth, View};
use App\Models\Gig;

/** Dashboard: a summary for the logged-in user. Applications and payments join it in later steps. */
class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();

        View::render('dashboard/index', [
            'title'  => 'Dashboard',
            'nav'    => 'dashboard',
            'user'   => $user,
            'counts' => $user['role'] === 'client' ? Gig::countsByStatus((int) $user['id']) : null,
            'open'   => Gig::countOpen(),
        ]);
    }
}
