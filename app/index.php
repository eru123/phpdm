<?php

require_once __DIR__ . '/autoload.php';

use App\Daemon;
use App\Crontab;

$date = date('Y-m-d H:i:s');
echo "[App][{$date}] Starting...\n";

try {
    Daemon::create(
        function () {
            $dt = new DateTime();
            if (Crontab::match('@hourly', $dt)) {
                echo "[App][{$dt->format('Y-m-d H:i:s')}] Hourly time Check\n";
            }
        }
    );

    Daemon::run();
} catch (Throwable $e) {
    $date = date('Y-m-d H:i:s');
    echo "[App][{$date}] Error: {$e->getMessage()}\n";
    echo "[App][{$date}] {$e->getTraceAsString()}\n";
}

echo "[App][{$date}] Exiting...\n";
