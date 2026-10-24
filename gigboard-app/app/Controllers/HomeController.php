<?php
namespace App\Controllers;

use App\Core\{Auth, Url, View};
use App\Models\Category;

class HomeController
{
    /** The public website for visitors; logged-in users go straight to their dashboard. */
    public function index(): void
    {
        if (Auth::check()) Url::redirect('/dashboard');

        View::render('home/welcome', [
            'title'      => 'GigBoard KE: local gigs for Kenyan youth',
            'categories' => Category::all(),
        ], 'layouts/bare');
    }
}
