<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgencyBackupService;
use Illuminate\Support\Facades\DB;

class BackupFull extends Command
{
    protected $signature = 'backup:full {agency_id}';
    protected $description = 'Full DB + files backup; filename uses agency name';

    public function handle(AgencyBackupService $svc): int
    {
        $aid = (int) $this->argument('agency_id');
        $agency = DB::table('agencies')->where('id', $aid)->first();
        if (!$agency) { $this->error("Agency {$aid} not found"); return self::FAILURE; }

        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$agency->name);
        $safe = preg_replace('/_+/', '_', trim($safe, '_-')); // تنسيق أنظف

        $name = $svc->createFull($safe);
        $this->info("Created: storage/app/agency_backups/{$name}");
        return self::SUCCESS;
    }
}
