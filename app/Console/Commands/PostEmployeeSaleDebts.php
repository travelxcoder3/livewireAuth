<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Sale, CommissionProfile};
use App\Services\EmployeeWalletService;
use Carbon\Carbon;

class PostEmployeeSaleDebts extends Command
{
    protected $signature = 'employee:post-sale-debts';
    protected $description = 'تقييد دين عمولة على الموظفين عند تجاوز days_to_debt بدون تحصيل كامل';

    public function handle()
    {
        $profiles = CommissionProfile::get()->keyBy('agency_id');
        $sales = Sale::with([
            'collections:id,sale_id,payment_date,amount'
        ])->withSum('collections','amount')
        ->where('status','!=','Void')
        ->get();


        $svc = app(EmployeeWalletService::class);
        $count = 0;

        foreach ($sales as $sale) {
            $profile = $profiles[$sale->agency_id] ?? null;
            $days = (int)($profile->days_to_debt ?? 0);
            if ($days <= 0) continue;

            // آخر سداد للعملية من collections، وإن لم يوجد فـ sale_date
            $lastPay  = optional($sale->collections)->max('payment_date') ?: $sale->sale_date;
            $deadline = \Carbon\Carbon::parse($lastPay)->addDays($days);
            if (now()->lt($deadline)) continue;


            $required  = (float)($sale->usd_sell ?? 0);
            $collected = (float)($sale->amount_paid ?? 0) + (float)($sale->collections_sum_amount ?? 0);
            if ($required <= 0 || $collected + 0.01 >= $required) continue;

            $svc->postSaleDebt($sale);
            $count++;
        }

        $this->info("تم تقييد {$count} دين عمولة.");
        return self::SUCCESS;
    }
}
