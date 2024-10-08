<?php

namespace App;

class Filebeat
{
    public static function list(string $paths): array
    {
        $res = [];

        $paths = explode(':', $paths);
        foreach ($paths as $path) {
            $res = array_merge($res, glob($path));
        }

        return $res;
    }
}
