<?php

namespace App\Integrations;

use App\Convert;
use App\Models\AnalyticsNginxAccessHostCounts;
use App\Models\AnalyticsNginxAccessHostSize;
use App\Models\AnalyticsNginxAccessStatusCounts;
use App\Models\AnalyticsNginxAccessUriCounts;
use App\Models\NginxAccessLogs;
use Wyue\Venv;

class NginxAccess extends AbstractIntegration
{
    public function transform($data)
    {
        if (!is_string($data) || empty($data)) return null;

        $rgx = [
            'proxy' => '/^\[(?<time_local>[^\]]+)\] (?<upstream_cache_status>[\d\-]{1,3}) (?<upstream_status>[\d\-]{1,3}) (?<status>[\d\-]{1,3}) - (?<request_method>[A-Z]+|-) (?<scheme>.*) (?<host>.*) "(?<request_uri>.*)" \[Client (?<remote_addr>.*)\] \[Length (?<body_bytes_sent>[\d\.\-]+)\] \[Gzip (?<gzip_ratio>[\d\.\-]+)\] \[Sent-to (?<server>.*)\] "(?<http_user_agent>.*)" "(?<http_referer>.*)"$/',
            'proxy_alt' => '/^\[(?<time_local>[^\]]+)\] (?<upstream_status>[\d\-]{1,3}) (?<status>[\d\-]{1,3}) - (?<request_method>[A-Z]+|-) (?<scheme>.*) (?<host>.*) "(?<request_uri>.*)" \[Client (?<remote_addr>.*)\] \[Length (?<body_bytes_sent>[\d\.\-]+)\] \[Gzip (?<gzip_ratio>[\d\.\-]+)\] \[Sent-to (?<server>.*)\] "(?<http_user_agent>.*)" "(?<http_referer>.*)"$/',
            'standard' => '/^\[(?<time_local>[^\]]+)\] (?<status>[\d\-]{1,3}) - (?<request_method>[A-Z]+|-) (?<scheme>.*) (?<host>.*) "(?<request_uri>.*)" \[Client (?<remote_addr>.*)\] \[Length (?<body_bytes_sent>[\d\.\-]+)\] \[Gzip (?<gzip_ratio>[\d\.\-]+)\] "(?<http_user_agent>.*)" "(?<http_referer>.*)"$/',
        ];

        foreach ($rgx as $type => $regex) {
            if (!preg_match($regex, $data, $matches)) {
                continue;
            }

            $uri = $matches['request_uri'];
            if (is_string($uri) && strpos($uri, '?') !== false) {
                $uri = substr($uri, 0, strpos($uri, '?'));
            }

            return [
                'message' => $data,
                'type' => $type,
                'timestamp' => Convert::dateFromToFormat($matches['time_local']),
                'upstream_cache_status' => isset($matches['upstream_cache_status']) && $matches['upstream_cache_status'] != '-' ? $matches['upstream_cache_status'] : null,
                'upstream_status' => isset($matches['upstream_status']) && $matches['upstream_status'] != '-' ? $matches['upstream_status'] : null,
                'status' => $matches['status'],
                'method' => $matches['request_method'],
                'scheme' => $matches['scheme'],
                'host' => $matches['host'],
                'uri' => $uri,
                'ip' => $matches['remote_addr'],
                'size' => $matches['body_bytes_sent'],
                'ratio' => $matches['gzip_ratio'],
                'server' => $matches['server'] ?? null,
                'user_agent' => $matches['http_user_agent'] == '-' ? null : $matches['http_user_agent'],
                'referer' => $matches['http_referer'] == '-' ? null : $matches['http_referer']
            ];
        }

        return null;
    }

    public function ingest($data = null)
    {
        if (empty($data)) return;

        // Logging
        if (!Venv::get('NGINX_ACCESS_ANALYTICS_ONLY', true)) {
            (new NginxAccessLogs)->insert($data);
        }

        // Channel Based Analytics
        (new AnalyticsNginxAccessHostCounts)->insertUpdateAnalytics(['unit' => 'hour'] + $data, 'host');
        (new AnalyticsNginxAccessHostCounts)->insertUpdateAnalytics(['unit' => 'day'] + $data, 'host');
        (new AnalyticsNginxAccessHostSize)->insertUpdateAnalytics(['unit' => 'hour'] + $data, 'host', 'size');
        (new AnalyticsNginxAccessHostSize)->insertUpdateAnalytics(['unit' => 'day'] + $data, 'host', 'size');

        // Identifier + Channel Based Analytics
        (new AnalyticsNginxAccessStatusCounts)->insertUpdateAnalytics(['unit' => 'hour'] + $data, 'host', 'status');
        (new AnalyticsNginxAccessStatusCounts)->insertUpdateAnalytics(['unit' => 'day'] + $data, 'host', 'status');
        (new AnalyticsNginxAccessUriCounts)->insertUpdateAnalytics(['unit' => 'hour'] + $data, 'host', 'uri');
        (new AnalyticsNginxAccessUriCounts)->insertUpdateAnalytics(['unit' => 'day'] + $data, 'host', 'uri');
    }
}
