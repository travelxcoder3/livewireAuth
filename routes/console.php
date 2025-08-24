<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\AutoSettlementService;
use App\Models\Customer;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/** تسوية المحافظ 02:00 */
Schedule::call(function () {
    Customer::with('wallet','agency')
        ->whereHas('wallet')
        ->chunkById(200, function ($chunk) {
            $svc = app(AutoSettlementService::class);
            foreach ($chunk as $customer) {
                $svc->autoSettle($customer, 'Scheduler');
            }
        });
})->dailyAt('02:00')
  ->name('auto-settlement')
  ->withoutOverlapping();

/** نسخ احتياطي 02:10 */
Schedule::command('agency:backup-all')
    ->dailyAt('02:10')
    ->name('agency-backup-all')
    ->withoutOverlapping();

/** تنظيف النسخ 02:20 */
Schedule::command('agency:prune-backups --keep-last=14')
    ->dailyAt('02:20')
    ->name('agency-prune-backups')
    ->withoutOverlapping();

/** ديون ما بعد البيع */
Schedule::command('employee:post-sale-debts')
    ->everyMinute()
    ->name('employee-post-sale-debts')
    ->withoutOverlapping();
