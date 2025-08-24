<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgencyBackupService;

class AgencyBackup extends Command
{
    protected $signature = 'agency:backup {agency_id}';
    protected $description = 'Create a per-agency backup ZIP';

    public function handle(AgencyBackupService $svc)
    {
        $aid  = (int) $this->argument('agency_id');
        $file = $svc->create($aid);
        $this->info("Backup created: storage/app/agency_backups/{$file}");
        return self::SUCCESS;
    }
}
