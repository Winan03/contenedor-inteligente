<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;

class LogClear extends Command
{
    protected $signature = 'log:clear';
    protected $description = 'Clear log file';

    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
            $this->info('Logs have been cleared!');
        } else {
            $this->info('Log file does not exist.');
        }
    }
}
