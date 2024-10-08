<?php

namespace Wyue\Migrations;

use Wyue\Database\AbstractMigration;

class SeekLogs extends AbstractMigration
{
    public function up()
    {
        $this->execute(<<<SQL
            CREATE TABLE `seek_logs` (
                `id` BIGINT NOT NULL AUTO_INCREMENT,
                `channel` VARCHAR(255) DEFAULT NULL,
                `file` TEXT NULL,
                `initialized` TINYINT(1) DEFAULT 1,
                `seek` BIGINT DEFAULT 0,
                `last_modified` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);
    }

    public function down()
    {
        $this->execute('DROP TABLE `seek_logs`');
    }
}
