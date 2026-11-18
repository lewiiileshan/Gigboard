<?php
namespace App\Controllers;

use App\Core\{Auth, View};

/** The Account tab: who you are and a log-out button. Editing the profile comes in a later step. */
class AccountController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        View::render('account/index', ['title' => 'Account', 'nav' => 'account', 'user' => $user]);
    }
}
