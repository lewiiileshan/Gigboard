<?php
namespace App\Core;

/**
 * Matches the requested URL and method to a controller method.
 *   GET /login      ->  AuthController::showLogin()
 *   GET /gigs/{id}  ->  GigController::show(12)   (the number in the URL is passed along)
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void  { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, array $handler): void
    {
        // "/gigs/{id}" becomes the pattern  #^/gigs/(?<id>[0-9]+)$#  (digits only)
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?<$1>[0-9]+)', $path) . '$#';
        $this->routes[$method][] = [$regex, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');

        // Support running in a sub-folder such as http://localhost/gigboard/public
        $base = Url::base();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = '/' . trim($path, '/');

        foreach ($this->routes[$method] ?? [] as [$regex, $handler]) {
            if (preg_match($regex, $path, $m)) {
                $args = [];
                foreach ($m as $key => $value) {
                    if (is_string($key)) $args[] = (int) $value;   // only the named {id} parts
                }
                [$class, $action] = $handler;
                (new $class())->$action(...$args);
                return;
            }
        }

        abort(404, 'Page not found');
    }
}
