<?php

namespace App\Models;

use Wyue\Database\AbstractModel;

class AnalyticsNginxAccessUriCounts extends AbstractModel
{
    use IdentifierBasedAnalyticsTrait;
    
    protected $table = 'analytics_nginx_access_uri_counts';
    protected $fillable = null;
    protected $hidden = null;
    protected $primaryKey = null;
}
 