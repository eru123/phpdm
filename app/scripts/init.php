<?php

require_once __DIR__ . '/autoload.php';

use App\Daemon;
use App\Migration;

$date = date('Y-m-d H:i:s');
echo "[App][{$date}] Setup starting...\n";

try {
    Migration::run(
        'App\Migration::sys_mem',
        'App\Migration::nginx_proxy_manager'
    );
} catch (Throwable $e) {
    $date = date('Y-m-d H:i:s');
    echo "[App][{$date}] Error: {$e->getMessage()}\n";
    echo "[App][{$date}] {$e->getTraceAsString()}\n";
}

echo "[App][{$date}] Setup ended\n";
