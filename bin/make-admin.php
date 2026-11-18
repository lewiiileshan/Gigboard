<?php
/**
 * Turn an existing account into an admin. Run it from a terminal, never from the website:
 *     php bin/make-admin.php person@example.com
 * Admins cannot be created through the register form, so there is no way to become one from the browser.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
require dirname(__DIR__) . '/app/Core/helpers.php';

$email = $argv[1] ?? '';
if ($email === '') { fwrite(STDERR, "Usage: php bin/make-admin.php person@example.com\n"); exit(1); }

if (\App\Models\User::makeAdmin($email)) { echo "$email is now an admin.\n"; exit(0); }
fwrite(STDERR, "No account found with the email $email. Register it on the website first.\n");
exit(1);
