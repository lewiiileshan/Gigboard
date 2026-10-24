<?php
// Settings in one place. Edit the database values to match your XAMPP MySQL.
// (On XAMPP the default user is "root" with an empty password.)
return [
    'app_name' => 'GigBoard KE',

    'db' => [
        'dsn'      => getenv('GB_DB_DSN')  ?: 'mysql:host=127.0.0.1;dbname=gigboard;charset=utf8mb4',
        'user'     => getenv('GB_DB_USER') !== false ? getenv('GB_DB_USER') : 'root',
        'password' => getenv('GB_DB_PASS') !== false ? getenv('GB_DB_PASS') : '',
    ],

    'session_name'     => 'gigboard_session',
    'password_min_len' => 8,
];
