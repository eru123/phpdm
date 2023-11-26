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

        if (!$stmt || $stmt?->rowCount() == 0) {
            echo "Creating table nginx_proxy_manager...\n";
            $orm = ORM::raw(
                <<<SQL
                CREATE TABLE `nginx_proxy_manager` (
                    `id` BIGINT NOT NULL AUTO_INCREMENT,
                    `message` TEXT NOT NULL,
                    `data` JSON NOT NULL,
                    INDEX `nginx_proxy_manager_data_type_index` ((CAST((`data`->>"$.type") AS CHAR(50)))),
                    INDEX `nginx_proxy_manager_data_timestamp_index` ((CAST((`data`->>"$.timestamp") AS DATETIME))),
                    INDEX `nginx_proxy_manager_data_host_index` ((CAST((`data`->>"$.host") AS CHAR(255)))),
                    INDEX `nginx_proxy_manager_data_ip_index` ((CAST((`data`->>"$.ip") AS CHAR(255)))),
                    INDEX `nginx_proxy_manager_data_method_index` ((CAST((`data`->>"$.method") AS CHAR(50)))),
                    INDEX `nginx_proxy_manager_data_protocol_index` ((CAST((`data`->>"$.protocol") AS CHAR(50)))),
                    INDEX `nginx_proxy_manager_data_path_index` ((CAST((`data`->>"$.path") AS CHAR(255)))),
                    INDEX `nginx_proxy_manager_data_size_index` ((CAST((`data`->>"$.size") AS CHAR(50)))),
                    INDEX `nginx_proxy_manager_data_status_index` ((CAST((`data`->>"$.http_code") AS CHAR(50)))),
                    INDEX `nginx_proxy_manager_data_referer_index` ((CAST((`data`->>"$.referer") AS CHAR(255)))),
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

    public static function run(...$migration) {
        foreach ($migration as $m) {
            Callback::make($m)();
        }
    }
}