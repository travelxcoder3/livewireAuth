<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\{Customer, Wallet, WalletTransaction, Sale, Collection};
use Illuminate\Support\Carbon;

class AutoSettlementService
{
   public function autoSettle(
    Customer $customer,
    ?string $performedByName = 'Auto-Settle',
    ?int $onlyEmployeeId = null
): float
    {
       return DB::transaction(function () use ($customer, $performedByName, $onlyEmployeeId) {
            $wallet = Wallet::where('customer_id', $customer->id)->lockForUpdate()->first();
            if (!$wallet || $wallet->status !== 'active') return 0.0;

            $available = (float) $wallet->balance;
            if ($available <= 0) return 0.0;

          $sales = Sale::with('collections')
    ->where('agency_id', $customer->agency_id)
    ->where('customer_id', $customer->id)
    ->when($onlyEmployeeId !== null, function ($q) use ($onlyEmployeeId) {
        $q->where('user_id', $onlyEmployeeId);
    })
    ->orderBy('sale_date')
    ->get();



          $debts = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id)
            ->map(function ($g) {
                $total = $g->sum('usd_sell');
                $paid  = $g->sum('amount_paid');
                $coll  = $g->flatMap->collections->sum('amount');
                $rem   = round($total - $paid - $coll, 2);
                $sale  = $g->first(); // سيحافظ على ترتيب sale_date بالأعلى
                return $rem > 0 ? (object)['sale_id'=>$sale->id,'remaining'=>$rem] : null;
            })
            ->filter()
            ->values();


            $applied = 0.0;

            foreach ($debts as $d) {
                if ($available <= 0) break;
                $pay = min($available, $d->remaining);
                if ($pay <= 0) continue;
                 Collection::create([
    'agency_id'    => $customer->agency_id,
    'sale_id'      => $d->sale_id,
    'amount'       => $pay,
    'payment_date' => Carbon::now()->toDateString(),
    'method'       => 'wallet',
    'note'         => 'تحصيل تلقائي من رصيد العميل',
    'user_id'      => $onlyEmployeeId ?? auth()->id(),
]);



                $newBal = round($available - $pay, 2);

                WalletTransaction::create([
                    'wallet_id'        => $wallet->id,
                    'type'             => 'withdraw',
                    'amount'           => $pay,
                    'running_balance'  => $newBal,
                    'reference'        => 'auto-settle',
                    'note'             => "سداد تلقائي على عملية #{$d->sale_id}",
                    'performed_by_name'=> $performedByName ?? (auth()->user()->name ?? 'Auto-Settle'),
                ]);

                $available = $newBal;
                $applied   = round($applied + $pay, 2);
            }

            if ($applied > 0) {
                $wallet->update(['balance' => $available]);
            }

            return $applied;
        });
    }
}
