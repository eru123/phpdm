<?php

require_once __DIR__ . '/vendor/autoload.php';

use Wyue\MySql;
use Wyue\Venv;

Venv::protect(true);
Venv::load_env(__DIR__ . '/.env', false);

date_default_timezone_set(Venv::get('TZ', 'Asia/Manila'));

MySql::set_config([
    'host' => Venv::get('DB_HOST', 'localhost'),
    'port' => Venv::get('DB_PORT', 3306),
    'user' => Venv::get('DB_USER', 'root'),
    'pass' => Venv::get('DB_PASS', null),
    'name' => Venv::get('DB_NAME', 'phpdm'),
    'migrations_table' => 'migrations',
    'migrations_path' => __DIR__ . '/db/migrations',
    'models_path' => __DIR__ . '/src/Models',
    'models_namespace' => 'App\Models',
]);
