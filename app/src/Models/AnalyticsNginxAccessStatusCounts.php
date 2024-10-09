<?php

namespace App\Models;

use Wyue\Database\AbstractModel;

class AnalyticsNginxAccessStatusCounts extends AbstractModel
{
    use IdentifierBasedAnalyticsTrait;
    
    protected $table = 'analytics_nginx_access_status_counts';
    protected $fillable = null;
    protected $hidden = null;
    protected $primaryKey = null;
}
 