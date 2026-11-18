<?php
// Used only for local testing with PHP's built-in server: serve real files, send everything else to index.php.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . '/../public' . $path)) return false;
$_SERVER['SCRIPT_NAME'] = '/index.php';   // PHP's test server sets this wrongly; Apache gets it right
require __DIR__ . '/../public/index.php';
