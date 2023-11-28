<?php

namespace App;

use eru123\orm\ORM;

class Migration
{
    public static function sys_mem()
    {
        $orm = ORM::raw('SHOW TABLES LIKE ?;', ['sys_mem']);
        $stmt = $orm->exec();
        if (!$stmt || $stmt?->rowCount() == 0) {
            echo "Creating table sys_mem...\n";
            $orm = ORM::raw(
                <<<SQL
                CREATE TABLE `sys_mem` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                SQL
            );
            $orm->exec();

            if ($orm->lastError()) {
                print_r($orm->lastError());
            }
        }
    }

    public static function nginx_proxy_manager()
    {
        $orm = ORM::raw('SHOW TABLES LIKE ?;', ['nginx_proxy_manager']);
        $stmt = $orm->exec();
        $date = date('Y-m-d H:i:s');
        if (!$stmt || $stmt?->rowCount() == 0) {
            echo "[Npm][$date] Creating table nginx_proxy_manager...\n";
            $orm = ORM::raw(
                <<<SQL
                CREATE TABLE `nginx_proxy_manager` (
                    `id` BIGINT NOT NULL AUTO_INCREMENT,
                    `message` TEXT DEFAULT NULL,
                    `type` VARCHAR(50) DEFAULT NULL,
                    `timestamp` DATETIME NOT NULL,
                    `host` VARCHAR(255) NOT NULL,
                    `ip` VARCHAR(255) DEFAULT NULL,
                    `method` VARCHAR(50) DEFAULT NULL,
                    `scheme` VARCHAR(50) DEFAULT NULL,
                    `uri` VARCHAR(255) DEFAULT NULL,
                    `size` BIGINT DEFAULT NULL,
                    `ratio` VARCHAR(255) DEFAULT NULL,
                    `server` VARCHAR(255) DEFAULT NULL,
                    `upstream_cache_status` VARCHAR(50) DEFAULT NULL,
                    `upstream_status` VARCHAR(50) DEFAULT NULL,
                    `status` VARCHAR(50) DEFAULT NULL,
                    `referer` VARCHAR(255) DEFAULT NULL,
                    `user_agent` VARCHAR(255) DEFAULT NULL,
                    INDEX `nginx_proxy_manager_type_index` (`type`),
                    INDEX `nginx_proxy_manager_timestamp_index` (`timestamp`),
                    INDEX `nginx_proxy_manager_host_index` (`host`),
                    INDEX `nginx_proxy_manager_ip_index` (`ip`),
                    INDEX `nginx_proxy_manager_method_index` (`method`),
                    INDEX `nginx_proxy_manager_scheme_index` (`scheme`),
                    INDEX `nginx_proxy_manager_uri_index` (`uri`),
                    INDEX `nginx_proxy_manager_size_index` (`size`),
                    INDEX `nginx_proxy_manager_status_index` (`status`),
                    INDEX `nginx_proxy_manager_referer_index` (`referer`),
                    INDEX `nginx_proxy_manager_user_agent_index` (`user_agent`),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                SQL
            );

            $orm->exec();

            if ($orm->lastError()) {
                print_r($orm->lastError());
            }
        }
    }

    public static function run(...$migration)
    {
        foreach ($migration as $m) {
            Callback::make($m)();
        }
    }
}