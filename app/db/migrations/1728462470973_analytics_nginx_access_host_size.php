<?php

namespace Wyue\Migrations;

use Wyue\Database\AbstractMigration;

class AnalyticsNginxAccessHostSize extends AbstractMigration
{
    public function up()
    {
        $this->execute(<<<SQL
            CREATE TABLE IF NOT EXISTS `analytics_nginx_access_host_size` (
                `channel` varchar(255) NOT NULL,
                `unit` varchar(255) NOT NULL DEFAULT 'day' COMMENT 'Unit of time for analytics',
                `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Datetime identifier for analytics',
                `value` BIGINT(64) NOT NULL DEFAULT (1),
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY `analytics_nginx_access_host_size_pk` (`channel`, `unit`, `timestamp`)
            )
            COLLATE='utf8mb4_bin'
            ENGINE=InnoDB;
        SQL);
    }

    public function down()
    {
        $this->execute('DROP TABLE `analytics_nginx_access_host_size`');
    }
}
