<?php
// Run from the project folder:  php bin/check-setup.php
// Checks the things that most often cause an HTTP 500 and explains each failure in plain words.
require __DIR__ . '/../app/Core/Config.php';
require __DIR__ . '/../app/Core/Database.php';
use App\Core\Config; use App\Core\Database;

$problems = 0;
function ok($m)   { echo "  OK    $m\n"; }
function bad($m, $fix) { global $problems; $problems++; echo "  FAIL  $m\n        Fix: $fix\n"; }

echo "GigBoard setup check\n\n";
version_compare(PHP_VERSION, '8.0.0', '>=') ? ok('PHP ' . PHP_VERSION) : bad('PHP ' . PHP_VERSION . ' is older than 8.0', 'install PHP 8 or newer');
foreach (['pdo', 'pdo_mysql', 'mbstring', 'fileinfo'] as $ext) {
    extension_loaded($ext) ? ok("PHP extension $ext") : bad("PHP extension $ext is missing", $ext === 'pdo_mysql' ? 'sudo apt install php-mysql, then restart Apache' : "install the php-$ext package, then restart Apache");
}
$dir = __DIR__ . '/../storage/deliveries';
(is_dir($dir) && is_writable($dir)) ? ok('storage/deliveries is writable')
    : bad('storage/deliveries is missing or not writable by the web server', 'sudo chown -R www-data:www-data storage   (on XAMPP: chmod -R 775 storage)');

$c = Config::get('db');
echo "\n  Using database settings: {$c['dsn']} as user '{$c['user']}'\n";
try {
    $pdo = Database::connect(); ok('Connected to the database');
    $need = ['users','freelancer_profiles','categories','gigs','applications','application_files','payments','submissions','reviews','admin_actions'];
    $have = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    $missing = array_diff($need, $have);
    $missing ? bad('Missing tables: ' . implode(', ', $missing), 'import database/schema.sql again (drop the gigboard database first)')
             : ok('All ' . count($need) . ' tables exist');
    $n = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $n > 0 ? ok("$n categories loaded") : bad('The categories table is empty', 'import database/schema.sql again');
} catch (\Throwable $e) {
    $m = $e->getMessage();
    $fix = 'check the dsn, user and password in config/config.php (or copy config/config.local.example.php to config.local.php)';
    if (stripos($m, 'Access denied') !== false && stripos($m, 'to database') !== false) $fix = "this user cannot open that database. Either the database name is wrong, or it was never created. Run: sudo mysql < database/schema.sql and grant the user rights on it";
    elseif (stripos($m, 'Access denied') !== false) $fix = "the user or password is wrong. On Ubuntu/MariaDB root uses socket login, so create a normal user:\n          sudo mysql -e \"CREATE USER 'gigboard'@'localhost' IDENTIFIED BY 'yourpassword'; GRANT ALL ON gigboard.* TO 'gigboard'@'localhost';\"\n          then put those details in config/config.local.php";
    if (stripos($m, 'Unknown database') !== false) $fix = 'the gigboard database does not exist yet. Run: sudo mysql < database/schema.sql';
    if (stripos($m, 'could not find driver') !== false) $fix = 'sudo apt install php-mysql, then restart Apache';
    if (stripos($m, 'Connection refused') !== false || stripos($m, 'No such file') !== false) $fix = 'MySQL is not running. Start it: sudo systemctl start mariadb';
    bad('Could not connect: ' . $m, $fix);
}
echo "\n" . ($problems ? "$problems problem(s) found. Fix them in order, then run this again.\n" : "Everything looks good. Open the site in your browser.\n");
exit($problems ? 1 : 0);
