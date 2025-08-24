<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgencyBackupService;

class AgencyRestore extends Command
{
    protected $signature = 'agency:restore {agency_id} {zip_filename}';
    protected $description = 'Restore per-agency data from a ZIP';

    public function handle(AgencyBackupService $svc)
    {
        $aid  = (int) $this->argument('agency_id');
        $file = (string) $this->argument('zip_filename');

        $svc->restore($aid, $file);
        $this->info('Restore completed.');
        return self::SUCCESS;
    }
}
