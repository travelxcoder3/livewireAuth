<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AgencyPruneBackups extends Command
{
    protected $signature = 'agency:prune-backups {--keep-last=14}';
    protected $description = 'Delete old agency backups, keeping the most recent N per agency';

    public function handle(): int
    {
        $keep = (int)$this->option('keep-last');
        $disk = Storage::disk('agency_backups');
        $agencies = DB::table('agencies')->pluck('id');

        foreach ($agencies as $agencyId) {
            $rows = DB::table('agency_backups')
                ->where('agency_id', $agencyId)
                ->orderByDesc('id')
                ->get();

            $delete = $rows->slice($keep); // ما بعد آخر N
            foreach ($delete as $b) {
                if ($disk->exists($b->filename)) {
                    $disk->delete($b->filename);
                }
                DB::table('agency_backups')->where('id', $b->id)->delete();
                $this->line("🗑 deleted {$b->filename} (agency {$agencyId})");
            }
        }

        $this->info('Prune completed.');
        return self::SUCCESS;
    }
}
