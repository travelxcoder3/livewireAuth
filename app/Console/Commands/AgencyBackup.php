<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgencyBackupService;
use Illuminate\Support\Facades\DB;

class AgencyBackup extends Command
{
    protected $signature = 'backup:full {agency_id}';
    protected $description = 'Full DB + files backup for specific agency (name used in filename)';

    public function handle(AgencyBackupService $svc): int
    {
        $aid = (int) $this->argument('agency_id');
        $agency = DB::table('agencies')->where('id', $aid)->first();

        if (!$agency) {
            $this->error("Agency {$aid} not found");
            return self::FAILURE;
        }

        // حول الاسم لاسم ملف صالح
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $agency->name);

        // مرر اسم الوكالة كـ tag
        $name = $svc->createFull($safeName);

        $this->info("Created: storage/app/agency_backups/{$name}");
        return self::SUCCESS;
    }
}
