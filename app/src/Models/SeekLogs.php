<?php

namespace App\Models;

use Generator;
use Wyue\Database\AbstractModel;
use Wyue\Commands\CLI;

class SeekLogs extends AbstractModel
{
    protected $table = 'seek_logs';
    protected $fillable = null;
    protected $hidden = null;
    protected $primaryKey = 'id';

    final static function tail(string $channel, string $path, int $seek = -1)
    {
        $modified = null;
        $create = true;

        $seeko = (new static)->find(['where' => ['channel' => $channel, 'file' => $path]]);
        if (empty($seeko)) {
            $seek = 0;
            $seeko = new static;
            $seeko->channel = $channel;
        } else {
            $create = false;
            $modified = strtotime($seeko->last_modified);
            if ($seek < 0) {
                $seek = $seeko->seek;
            }
        }

        clearstatcache(true, $path);
        $last_modified = filemtime($path);

        if (!file_exists($path)) {
            CLI::warning("File not found: " . $path);
        }

        if ($modified !== $last_modified) {
            $f = fopen($path, 'r');
            $fstat = fstat($f);
            $fsize = $fstat['size'];

            if ($seek > $fsize) {
                $seek = 0;
            }

            fseek($f, $seek);

            try {
                while ($line = fgets($f)) {
                    yield $line;
                }
            } finally {
                $seek = ftell($f);
                $seeko->seek = $seek;
                $seeko->file = $path;
                $seeko->last_modified = date('Y-m-d H:i:s', $last_modified);
                if ($create) {
                    $seeko->initialized = 1;
                    $seeko->insert();
                } else {
                    $seeko->update();
                }
                fclose($f);
            }
        }
    }
}
