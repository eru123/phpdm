<?php

namespace App;

use DateTime;

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

    public static function dateFromToFormat($datetime, $format = 'Y-m-d H:i:s')
    {
        $datetime = new DateTime($datetime);
        return $datetime->format($format);
    }
}