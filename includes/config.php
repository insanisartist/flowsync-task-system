<?php

declare(strict_types=1);

// Здесь выбирается активная среда: local или production.
if (!defined('ENV_TYPE')) {
    define('ENV_TYPE', 'local');
}

$config = [
    'local' => [
        'app_name' => 'FlowSync',
        'base_url' => 'http://localhost/diplom/htdocs',
        'db' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'diplom_tasks',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
    ],
    'production' => [
        'app_name' => 'FlowSync',
        'base_url' => 'https://artifex123insanis.cloudpub.ru',
        'db' => [
            'host' => 'localhost',
            'port' => '3306',
            'dbname' => 'cloudpub_db_name',
            'username' => 'cloudpub_db_user',
            'password' => 'cloudpub_db_password',
            'charset' => 'utf8mb4',
        ],
    ],
];

return $config[ENV_TYPE];
