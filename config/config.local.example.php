<?php
// Copy this file to config.local.php and put your own database details here.
// config.local.php is ignored by git, so your password is never uploaded.
return [
    'db' => [
        'dsn'      => 'mysql:host=127.0.0.1;dbname=gigboard;charset=utf8mb4',
        'user'     => 'gigboard',
        'password' => 'choose-a-password',
    ],
];
