<?php

namespace App\Models;

use Wyue\Database\AbstractModel;

class AnalyticsNginxAccessHostCounts extends AbstractModel
{
    use AnalyticsTrait;

    protected $table = 'analytics_nginx_access_host_counts';
    protected $fillable = null;
    protected $hidden = null;
    protected $primaryKey = null;
}
 