<?php

namespace App\Commands;

use App\Daemon;
use App\Crontab;
use Wyue\Commands\AbstractCommand;

class NginxAccess extends AbstractCommand
{
    protected string $entry = 'run:entrypoint';
    protected string $description = 'Run the application';
    protected array $flags = [
        'V|verbose' => 'Enable verbose mode',
    ];

    public function handle()
    {
        CLI::println("[App][" . date('Y-m-d H:i:s') . "] Started");

        try {
            Daemon::create(
                function () {
                    if (Crontab::match('@hourly', $dt)) {
                        CLI::println("[App][" . date('Y-m-d H:i:s') . "] Hourly time Check");
                    }
                }
            );

            Daemon::run();
        } catch (\Throwable $e) {
            $date = date('Y-m-d H:i:s');
            CLI::error("[App][" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage());
            !$this->flag('V|verbose') || CLI::error("[App][" . date('Y-m-d H:i:s') . "] " . $e->getTraceAsString());
        }

        CLI::error("[App][" . date('Y-m-d H:i:s') . "] Exiting...");
        exit(1);
    }
}
