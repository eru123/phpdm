<?php

require_once __DIR__ . '/autoload.php';

use App\Daemon;
use App\Migration;

$date = date('Y-m-d H:i:s');
echo "[Ram][{$date}] Starting...\n";

try {
    Daemon::create(
        'App\Collector::sys_mem'
    );

    Daemon::run();
} catch (Throwable $e) {
    $date = date('Y-m-d H:i:s');
    echo "[Ram][{$date}] Error: {$e->getMessage()}\n";
    echo "[Ram][{$date}] {$e->getTraceAsString()}\n";
}

echo "[Ram][{$date}] Exiting...\n";
