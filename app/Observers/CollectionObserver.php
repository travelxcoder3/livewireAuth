<?php

namespace App\Observers;

use App\Models\Collection;
use App\Services\EmployeeWalletService;

class CollectionObserver
{
    public function created(Collection $collection): void
    {
        $sale = $collection->sale()->withSum('collections','amount')->first();
        if (!$sale) return;

        $collected = (float)($sale->amount_paid ?? 0) + (float)($sale->collections_sum_amount ?? 0);
        $required  = (float)($sale->usd_sell ?? 0);

        if ($required > 0 && $collected + 0.01 >= $required) {
            app(EmployeeWalletService::class)->releaseDebtAndPostCommission($sale);
        }
    }
}
