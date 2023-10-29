<?php

require_once __DIR__ . '/autoload.php';

use App\Daemon;

try {
    Daemon::create(function () {
        $date = date('Y-m-d H:i:s');
        echo "[{$date}] Hello World!\n";
    });
    Daemon::run();
} catch (Exception $e) {
    $date = date('Y-m-d H:i:s');
    echo "[{$date}] Error {$e->getMessage()}\n";
    echo "[{$date}] {$e->getTraceAsString()}\n";
}

echo "[{$date}] Exiting...\n";
