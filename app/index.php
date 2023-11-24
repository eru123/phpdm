<?php

require_once __DIR__ . '/autoload.php';

use App\Daemon;
use App\Crontab;

$date = date('Y-m-d H:i:s');
echo "[PHPDM][{$date}] Starting...\n";

try {
    Daemon::create(
        function () {
            $dt = new DateTime();
            if (Crontab::match('*/5 * * * *', $dt)) {
                echo "[PHPDM][{$dt->format('Y-m-d H:i:s')}] 5 Minute time Check\n";
            }
        }
    );

    Daemon::run();
} catch (Throwable $e) {
    $date = date('Y-m-d H:i:s');
    echo "[PHPDM][{$date}] Error: {$e->getMessage()}\n";
    echo "[PHPDM][{$date}] {$e->getTraceAsString()}\n";
}

echo "[PHPDM][{$date}] Exiting...\n";
