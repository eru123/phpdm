<?php

namespace Wyue\Migrations;

use Wyue\Database\AbstractMigration;

class NginxAccessLogs extends AbstractMigration
{
    public function up()
    {
        $this->execute(<<<SQL
            CREATE TABLE `nginx_access_logs` (
                `id` BIGINT NOT NULL AUTO_INCREMENT,
                `message` TEXT DEFAULT NULL,
                `type` VARCHAR(50) DEFAULT NULL,
                `timestamp` DATETIME NOT NULL,
                `host` VARCHAR(255) NOT NULL,
                `ip` VARCHAR(255) DEFAULT NULL,
                `method` VARCHAR(50) DEFAULT NULL,
                `scheme` VARCHAR(50) DEFAULT NULL,
                `uri` TEXT NULL,
                `size` BIGINT DEFAULT NULL,
                `ratio` VARCHAR(255) DEFAULT NULL,
                `server` VARCHAR(255) DEFAULT NULL,
                `upstream_cache_status` VARCHAR(50) DEFAULT NULL,
                `upstream_status` VARCHAR(50) DEFAULT NULL,
                `status` VARCHAR(50) DEFAULT NULL,
                `referer` TEXT NULL,
                `user_agent` TEXT NULL,
                INDEX `nginx_access_logs_timestamp_index` (`timestamp`),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);
    }

    public function down()
    {
        $this->execute('DROP TABLE `nginx_access_logs`');
    }
}
