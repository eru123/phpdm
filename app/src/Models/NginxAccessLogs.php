<?php

namespace App\Models;

use Wyue\Database\AbstractModel;

class NginxAccessLogs extends AbstractModel
{
    protected $table = 'nginx_access_logs';
    protected $fillable = null;
    protected $hidden = null;
    protected $primaryKey = 'id';
}
