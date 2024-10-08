<?php

namespace App\Commands;

use App\Daemon;
use App\Crontab;
use App\Filebeat;
use App\Models\SeekLogs;
use App\Integrations\NginxAccess as NginxAccessIntegration;
use Wyue\Venv;
use Wyue\Commands\AbstractCommand;
use Wyue\Commands\CLI;

class NginxAccess extends AbstractCommand
{
    protected string $entry = 'run:nginx_access_logs';
    protected string $description = 'Run the application';
    protected array $flags = [
        'V|verbose' => 'Enable verbose mode',
    ];

    public function handle()
    {
        CLI::println("[Nginx Access][" . date('Y-m-d H:i:s') . "] Started");

        try {
            Daemon::create(static::class . '::collector');
            Daemon::run();
        } catch (\Throwable $e) {
            CLI::error("[Nginx Access][" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage());
            !$this->flag('V|verbose') || CLI::error("[Nginx Access][" . date('Y-m-d H:i:s') . "] " . $e->getTraceAsString());
        }

        CLI::error("[Nginx Access][" . date('Y-m-d H:i:s') . "] Exiting...");
        exit(1);
    }

    public static function collector()
    {
        if (Crontab::match(Venv::get(['NGINX_ACCESS_INTERVAL', 'NGINX_INTERVAL'], '*/10 * * * * *'), new \DateTime())) {
            $files = Filebeat::list(Venv::get(['NGINX_ACCESS_LOGS_PATH', 'NGINX_LOGS_PATH'], '/var/log/nginx/*access.log'));

            $integration = new NginxAccessIntegration();
            
            foreach ($files as $file) {
                $cnt_process = 0;
                $cnt_invalid = 0;
                foreach (SeekLogs::tail('nginx_access', $file) as $log) {
                    $data = $integration->transform($log);
                    if (!is_null($data)) {
                        $integration->ingest($data);
                        $cnt_process++;
                    } else {
                        echo $log . PHP_EOL;
                        $cnt_invalid++;
                    }
                }

                if ($cnt_invalid == 0 && $cnt_process == 0) {
                    continue;
                }

                CLI::println("[Nginx Access][" . date('Y-m-d H:i:s') . "] Collected {$cnt_process} and discarded {$cnt_invalid} logs from {$file}");
            }
        }
    }
}
