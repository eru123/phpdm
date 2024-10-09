<?php

namespace App\Models;

use Wyue\MySql;
use Wyue\Venv;

trait IdentifierBasedAnalyticsTrait
{
    public function parseAnalytics(array $data, string $identifierKey = null, string $channelKey = null, string $valueKey = null)
    {
        if (!isset($data['id'])) {
            $data['id'] = 0;
        } else {
            $data['id'] = intval($data['id']);
        }

        if (!isset($data['channel'])) {
            $data['channel'] = strval(!is_null($channelKey) ? Venv::_get($data, $channelKey, 'default') : 'default');
        }

        if (!isset($data['timestamp'])) {
            $data['timestamp'] = date('Y-m-d H:i:s');
        }

        if (!isset($data['unit'])) {
            $data['unit'] = 'day';
        }

        if (!isset($data['value'])) {
            $data['value'] = intval(!is_null($valueKey) ? Venv::_get($data, $valueKey, 1) : 1);
        } else {
            $data['value'] = intval($data['value']);
        }

        if (!isset($data['identifier'])) {
            $data['identifier'] = strval(!is_null($identifierKey) ? Venv::_get($data, $identifierKey, 'default') : 'default');
        }

        $data['timestamp'] = match ($data['unit']) {
            'year' => date('Y-01-01 00:00:00', strtotime($data['timestamp'])),
            'month' => date('Y-m-01 00:00:00', strtotime($data['timestamp'])),
            'day' => date('Y-m-d 00:00:00', strtotime($data['timestamp'])),
            'hour' => date('Y-m-d H:00:00', strtotime($data['timestamp'])),
            'minute' => date('Y-m-d H:i:00', strtotime($data['timestamp'])),
            'second' => date('Y-m-d H:i:s', strtotime($data['timestamp'])),
            default => $data['timestamp'],
        };

        return $data;
    }

    public function insertUpdateAnalytics(array $data, string $identifierKey, string $channelKey = null, string $valueKey = null): bool
    {
        $data = $this->parseAnalytics($data, $identifierKey, $channelKey, $valueKey);
        $table = $this->table;

        $sql = MySql::raw(<<<SQL
            INSERT INTO `?` (`identifier`, `channel`, `value`, `unit`, `timestamp`)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE `value` = `value` + ?
        SQL, [
            MySql::raw($table),
            $data['identifier'],
            $data['channel'],
            $data['value'],
            $data['unit'],
            $data['timestamp'],
            $data['value'],
        ]);

        return MySql::raw($sql)->exec()?->rowCount() > 0;
    }
}
