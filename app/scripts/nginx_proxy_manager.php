<?php

require_once __DIR__ . '/autoload.php';

use App\Daemon;
use App\Migration;

$date = date('Y-m-d H:i:s');
echo "[Npm][{$date}] Starting...\n";

try {
    Migration::run(
        'App\Collector::nginx_proxy_manager_init'
    );

    Daemon::create(
        'App\Collector::nginx_proxy_manager'
    );

    Daemon::run();
} catch (Throwable $e) {
    $date = date('Y-m-d H:i:s');
    echo "[Npm][{$date}] Error: {$e->getMessage()}\n";
    echo "[Npm][{$date}] {$e->getTraceAsString()}\n";
}

echo "[Npm][{$date}] Exiting...\n";
