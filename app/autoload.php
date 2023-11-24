<?php

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/vendor/autoload.php';

use eru123\orm\ORM;

venv_protect();
venv_load(__DIR__ . '/.env', false);

ORM::set_pdo([
    'driver' => 'mysql',
    'host' => venv('DB_HOST'),
    'port' => venv('DB_PORT'),
    'user' => venv('DB_USER'),
    'pass' => venv('DB_PASS'),
    'name' => venv('DB_NAME')
]);