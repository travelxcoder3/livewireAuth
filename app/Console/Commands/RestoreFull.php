<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgencyBackupService;

class RestoreFull extends Command
{
    protected $signature = 'backup:restore-full {zip}';
    protected $description = 'Restore a full backup ZIP (DB + files)';

    public function handle(AgencyBackupService $svc): int
    {
        $svc->restoreFull($this->argument('zip'));
        $this->info('Full restore completed. Caches cleared.');
        return self::SUCCESS;
    }
}
