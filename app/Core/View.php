<?php
namespace App\Core;

/** Loads a template from app/Views and wraps it in a layout. */
class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/main'): void
    {
        $viewsDir = dirname(__DIR__) . '/Views/';
        $flash = Session::pullFlash();   // one-time message, available to the page and the layout
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewsDir . $view . '.php';
        $content = ob_get_clean();          // the page's own HTML

        require $viewsDir . $layout . '.php'; // the layout prints $content
        Session::clearOld();                  // typed-in values are shown once, then forgotten
    }
}
