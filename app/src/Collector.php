<?php

namespace App;

use eru123\orm\ORM;
use Throwable;
use DateTime;
use Wyue\MySql;

class Collector
{
    static $ffseek = [];
    static $npm_log_count = 0;

    public static function tail($file)
    {
        $f = fopen($file, 'r');
        $fstat = fstat($f);
        $fsize = $fstat['size'];

        if (!isset(self::$ffseek[$file])) {
            self::$ffseek[$file] = 0;
        }

        if (self::$ffseek[$file] > $fsize) {
            self::$ffseek[$file] = 0;
        }

        fseek($f, self::$ffseek[$file]);

        try {
            while ($line = fgets($f)) {
                yield $line;
            }
        } finally {
            self::$ffseek[$file] = ftell($f);
            fclose($f);
        }
    }

    public static function seek_end_tail($file)
    {
        if (!isset(self::$ffseek[$file])) {
            $f = fopen($file, 'r');
            $fstat = fstat($f);
            $fsize = $fstat['size'];
            fseek($f, $fsize);
            $line = '';
            while ($line = fgets($f))
                ;
            static::$ffseek[$file] = ftell($f);
            fclose($f);
        }
    }

    public static function sys_mem()
    {
        $date = date('Y-m-d H:i:s');
        $f = fopen(venv('ROOTFS_PATH', '/app/rootfs') . '/proc/meminfo', 'r');
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

        $orm = MySql::insert('sys_mem', $data);
        $orm->exec();
        if ($orm->lastError()) {
            echo "[{$date}] Error: ";
            print_r($orm->lastError());
        }
    }

    public static function nginx_proxy_manager_access_logs()
    {
        $res = [];
        $path = venv('NGINX_PROXY_MANAGER_DATA_PATH');
        if (empty($path)) {
            return $res;
        }

        $rootfs = venv('ROOTFS_PATH', '/app/rootfs');
        $f = realpath($rootfs . '/' . ltrim($path, '/'));
        if (empty($f) || !is_dir($f)) {
            return $res;
        }

        $flogs = realpath($f . '/logs');
        return glob($flogs . '/*_access.log');
    }

    public static function nginx_proxy_manager_init()
    {
        $date = date('Y-m-d H:i:s');
        echo "[Npm][{$date}] Initializing...\n";
        $logs = static::nginx_proxy_manager_access_logs();
        echo "[Npm][{$date}] Seeking end of " . count($logs) . " files...\n";
        foreach ($logs as $lfile) {
            static::seek_end_tail($lfile);
        }
    }

    public static function nginx_proxy_manager()
    {
        $date = new DateTime();
        $last_log_count = static::$npm_log_count;
        static::$npm_log_count = 0;

        if (Crontab::match('@hourly', $date)) {
            echo "[Npm][" . $date->format('Y-m-d H:i:s') . "] Collected {$last_log_count} npm logs in the last hour.\n";
        }

        $rgx = [
            'proxy' => '/^\[(?<time_local>.*)\] (?<upstream_cache_status>[\d-]{1,3}) (?<upstream_status>[\d-]{1,3}) (?<status>[\d-]{1,3}) - (?<request_method>[A-Z]+) (?<scheme>.*) (?<host>.*) "(?<request_uri>.*)" \[Client (?<remote_addr>.*)\] \[Length (?<body_bytes_sent>[\d]+)\] \[Gzip (?<gzip_ratio>[\d\.]+)\] \[Sent-to (?<server>.*)\] "(?<http_user_agent>.*)" "(?<http_referer>.*)"$/',
            'standard' => '/^\[(?<time_local>.*)\] (?<status>[\d-]{1,3}) - (?<request_method>[A-Z]+) (?<scheme>.*) (?<host>.*) "(?<request_uri>.*)" \[Client (?<remote_addr>.*)\] \[Length (?<body_bytes_sent>[\d\.]+)\] \[Gzip (?<gzip_ratio>[\d\.]+)\] "(?<http_user_agent>.*)" "(?<http_referer>.*)"$/'
        ];

        $logs = static::nginx_proxy_manager_access_logs();
        $data_count = 0;
        $error_count = 0;
        $first_error = null;
        $last_error = null;
        foreach ($logs as $lfile) {
            foreach (self::tail($lfile) as $line) {
                foreach ($rgx as $type => $regex) {
                    $match = preg_match($regex, $line, $matches);
                    if (!$match) {
                        continue;
                    }

                    $data = [
                        'message' => $line,
                        'type' => $type,
                        'timestamp' => Convert::dateFromToFormat($matches['time_local']),
                        'upstream_cache_status' => isset($matches['upstream_cache_status']) && $matches['upstream_cache_status'] != '-' ? $matches['upstream_cache_status'] : null,
                        'upstream_status' => isset($matches['upstream_status']) && $matches['upstream_status'] != '-' ? $matches['upstream_status'] : null,
                        'status' => $matches['status'],
                        'method' => $matches['request_method'],
                        'scheme' => $matches['scheme'],
                        'host' => $matches['host'],
                        'uri' => $matches['request_uri'],
                        'ip' => $matches['remote_addr'],
                        'size' => $matches['body_bytes_sent'],
                        'ratio' => $matches['gzip_ratio'],
                        'server' => $matches['server'] ?? null,
                        'user_agent' => $matches['http_user_agent'] == '-' ? null : $matches['http_user_agent'],
                        'referer' => $matches['http_referer'] == '-' ? null : $matches['http_referer']
                    ];

                    $orm = MySql::insert('nginx_proxy_manager', $data);
                    $orm->exec(false);
                    if ($orm->lastError()) {
                        $error_count++;
                        if (is_null($first_error)) {
                            $first_error = $orm->lastError();
                        } else {
                            $last_error = $orm->lastError();
                        }
                    } else {
                        $data_count++;
                    }
                }
            }
        }

        if ($first_error) {
            echo "[Npm][{$date->format('Y-m-d H:i:s')}] Error: {$first_error}\n";
        }

        if ($last_error) {
            echo "[Npm][{$date->format('Y-m-d H:i:s')}] Error: {$last_error}\n";
        }

        if ($data_count > 0) {
            echo "[Npm][{$date->format('Y-m-d H:i:s')}] Collected {$data_count} npm logs.\n";
        }

        if ($error_count > 0) {
            echo "[Npm][{$date->format('Y-m-d H:i:s')}] Error: {$error_count} npm logs.\n";
        }

        static::$npm_log_count += $data_count;
    }
}