<?php
namespace App\Controllers;

use App\Core\{Auth, View};

/** Static pages: terms of service and privacy policy (draft text for the project). */
class PageController
{
    public function terms(): void   { $this->page('terms', 'Terms of service'); }
    public function privacy(): void { $this->page('privacy', 'Privacy policy'); }

    private function page(string $view, string $title): void
    {
        View::render('pages/' . $view, ['title' => $title], Auth::check() ? 'layouts/main' : 'layouts/bare');
    }
}
