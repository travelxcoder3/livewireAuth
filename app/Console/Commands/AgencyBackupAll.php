<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\AgencyBackupService;

class AgencyBackupAll extends Command
{
    protected $signature = 'agency:backup-all {--agency=* : IDs to limit}';
    protected $description = 'Create daily backups for all agencies (or selected ones)';

    public function handle(AgencyBackupService $svc): int
    {
        $ids = collect($this->option('agency'))->filter()->map(fn($v)=>(int)$v);
        if ($ids->isEmpty()) {
            $ids = DB::table('agencies')->pluck('id');
        }

        $this->info('Starting backups for agencies: '.implode(',', $ids->all()));
        $ok = 0; $fail = 0;

        foreach ($ids as $id) {
            try {
                $file = $svc->create((int)$id);
                $this->line("✔ agency {$id} -> {$file}");
                $ok++;
            } catch (\Throwable $e) {
                $this->error("✖ agency {$id} failed: ".$e->getMessage());
                $fail++;
            }
        }

        $this->info("Done. success={$ok}, failed={$fail}");
        return $fail ? self::FAILURE : self::SUCCESS;
    }
}
