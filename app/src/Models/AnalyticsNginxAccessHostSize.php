<?php

namespace App\Models;

use Wyue\Database\AbstractModel;

class AnalyticsNginxAccessHostSize extends AbstractModel
{
    use AnalyticsTrait;

    protected $table = 'analytics_nginx_access_host_size';
    protected $fillable = null;
    protected $hidden = null;
    protected $primaryKey = null;
}
 