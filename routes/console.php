<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Services\AutoSettlementService;
use App\Models\Customer;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



Schedule::call(function () {
    Customer::with('wallet','agency')
        ->whereHas('wallet')                 // فقط من لديه محفظة
        ->chunkById(200, function ($chunk) {
            $svc = app(AutoSettlementService::class);
            foreach ($chunk as $customer) {
                $svc->autoSettle($customer, 'Scheduler');
            }
        });
})->dailyAt('02:00');   

Schedule::command('employee:post-sale-debts')->everyMinute();
