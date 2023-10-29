<?php

// set timezone
date_default_timezone_set('Asia/Manila');
define('ROOTFS', '/app/rootfs');

require_once __DIR__ . '/autoload.php';

use App\Daemon;
use App\ORM;

// Convert Bytes
class Convert
{
    public static function toBytes($value, $unit = 'B')
    {
        $value = (int) $value;
        switch ($unit) {
            case 'KB':
            case 'kB':
            case 'K':
            case 'k':
                return $value * 1024;
            case 'MB':
            case 'mB':
            case 'M':
            case 'm':
                return $value * 1024 * 1024;
            case 'GB':
            case 'gB':
            case 'G':
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'TB':
            case 'tB':
            case 'T':
            case 't':
                return $value * 1024 * 1024 * 1024 * 1024;
            default:
                return $value;
        }
    }

    public static function fromBytes($value, $unit = 'B')
    {
        $value = (int) $value;
        switch ($unit) {
            case 'KB':
            case 'kB':
            case 'K':
            case 'k':
                return $value / 1024;
            case 'MB':
            case 'mB':
            case 'M':
            case 'm':
                return $value / 1024 / 1024;
            case 'GB':
            case 'gB':
            case 'G':
            case 'g':
                return $value / 1024 / 1024 / 1024;
            case 'TB':
            case 'tB':
            case 'T':
            case 't':
                return $value / 1024 / 1024 / 1024 / 1024;
            default:
                return $value;
        }
    }
}

try {
    $orm = ORM::raw('SHOW TABLES LIKE ?;', ['sys_mem']);
    $stmt = $orm->exec();
    if (!$stmt || $stmt?->rowCount() == 0) {
        echo "Creating table sys_mem...\n";
        $orm = ORM::raw('CREATE TABLE `sys_mem` (
            `id` BIGINT NOT NULL AUTO_INCREMENT,
            `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `mem_total` BIGINT NOT NULL,
            `mem_free` BIGINT NOT NULL,
            `mem_available` BIGINT NOT NULL,
            `mem_used` BIGINT NOT NULL,
            `mem_used_percent` FLOAT NOT NULL,
            `mem_cache` BIGINT NOT NULL,
            `mem_buffer` BIGINT NOT NULL,
            `mem_cache_percent` FLOAT NOT NULL,
            PRIMARY KEY (`id`),
            KEY `sys_mem_timestamp_index` (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $orm->exec();

        if ($orm->lastError()) {
            print_r($orm->lastError());
        }
    }

    Daemon::create(function () {
        $date = date('Y-m-d H:i:s');
        $f = fopen(ROOTFS . '/proc/meminfo', 'r');
        $data = [];
        while ($line = fgets($f)) {
            if (preg_match('/^(\w+):\s+(\d+)(\s+?(\w+))?/', $line, $matches)) {
                $data[$matches[1]] = [
                    'value' => $matches[2],
                    'unit' => $matches[4] ?? 'B'
                ];
            }
        }
        fclose($f);

        $mem_total = (int) Convert::toBytes($data['MemTotal']['value'], $data['MemTotal']['unit']);
        $mem_free = (int) Convert::toBytes($data['MemFree']['value'], $data['MemFree']['unit']);
        $mem_available = (int) Convert::toBytes($data['MemAvailable']['value'], $data['MemAvailable']['unit']);
        $mem_used = $mem_total - $mem_free;
        $mem_used_percent = $mem_used / $mem_total * 100;
        $mem_cache = (int) Convert::toBytes($data['Cached']['value'], $data['Cached']['unit']);
        $mem_buffer = (int) Convert::toBytes($data['Buffers']['value'], $data['Buffers']['unit']);
        $mem_cache_percent = ($mem_cache + $mem_buffer) / $mem_total * 100;

        $data = [
            'timestamp' => $date,
            'mem_total' => Convert::fromBytes($mem_total, 'MB'),
            'mem_free' => Convert::fromBytes($mem_free, 'MB'),
            'mem_available' => Convert::fromBytes($mem_available, 'MB'),
            'mem_used' => Convert::fromBytes($mem_used, 'MB'),
            'mem_used_percent' => $mem_used_percent,
            'mem_cache' => Convert::fromBytes($mem_cache, 'MB'),
            'mem_buffer' => Convert::fromBytes($mem_buffer, 'MB'),
            'mem_cache_percent' => $mem_cache_percent
        ];
        $orm = ORM::insert('sys_mem', $data);
        $orm->exec();
        if ($orm->lastError()) {
            echo "[{$date}] Error: ";
            print_r($orm->lastError());
        }
    });
    Daemon::run();
} catch (Throwable $e) {
    $date = date('Y-m-d H:i:s');
    echo "[{$date}] Error: {$e->getMessage()}\n";
    echo "[{$date}] {$e->getTraceAsString()}\n";
}

echo "[{$date}] Exiting...\n";
