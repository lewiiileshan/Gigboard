<?php
/**
 * FRONT CONTROLLER: every request to the site enters here (see public/.htaccess).
 * It loads the code, starts the session, defines the routes and runs the router.
 */
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    // App\Controllers\AuthController  ->  app/Controllers/AuthController.php
    if (!str_starts_with($class, 'App\\')) return;
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
require dirname(__DIR__) . '/app/Core/helpers.php';

use App\Controllers\{AccountController, AuthController, DashboardController, GigController, HomeController};
use App\Core\{Router, Session};

date_default_timezone_set('Africa/Nairobi');
Session::start();

$router = new Router();
$router->get('/',          [HomeController::class,      'index']);
$router->get('/register',  [AuthController::class,      'showRegister']);
$router->post('/register', [AuthController::class,      'register']);
$router->get('/login',     [AuthController::class,      'showLogin']);
$router->post('/login',    [AuthController::class,      'login']);
$router->post('/logout',   [AuthController::class,      'logout']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/account',   [AccountController::class,   'index']);

// Gigs (step 2)
$router->get('/gigs',                [GigController::class, 'index']);
$router->get('/gigs/create',         [GigController::class, 'create']);
$router->post('/gigs/create',        [GigController::class, 'store']);
$router->get('/gigs/{id}',           [GigController::class, 'show']);
$router->get('/gigs/{id}/edit',      [GigController::class, 'edit']);
$router->post('/gigs/{id}/edit',     [GigController::class, 'update']);
$router->post('/gigs/{id}/cancel',   [GigController::class, 'cancel']);
$router->get('/my-gigs',             [GigController::class, 'mine']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
