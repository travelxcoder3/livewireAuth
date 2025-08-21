<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\{Customer, Wallet, WalletTransaction, Sale, Collection};

class AutoSettlementService
{
   public function autoSettle(
    Customer $customer,
    ?string $performedByName = 'Auto-Settle',
    ?int $onlyEmployeeId = null,
    ?int $collectorUserId = null,
    ?int $collectorMethod = null
): float {
    // لوج تشخيصي مختصر
    \Log::info('SVC.autoSettle.args', [
        'customer_id'     => $customer->id,
        'onlyEmployeeId'  => $onlyEmployeeId,
        'collectorUserId' => $collectorUserId,
        'collectorMethod' => $collectorMethod,
    ]);

    // إن كنت تريد فرض تمرير بيانات المُحصّل
    if ($onlyEmployeeId === null && ($collectorUserId === null || $collectorMethod === null)) {
        \Log::warning('SVC.skip_legacy_call_without_collector_params');
        return 0.0;
    }

    return DB::transaction(function () use ($customer, $performedByName, $onlyEmployeeId, $collectorUserId, $collectorMethod) {
        $wallet = Wallet::where('customer_id', $customer->id)->lockForUpdate()->first();
        if (!$wallet || $wallet->status !== 'active') return 0.0;

        $available = (float) $wallet->balance;
        if ($available <= 0) return 0.0;

        // المبيعات المرشّحة للسداد
        $sales = Sale::with('collections')
            ->where('agency_id',  $customer->agency_id)
            ->where('customer_id', $customer->id)
            ->when($onlyEmployeeId !== null, fn ($q) => $q->where('user_id', $onlyEmployeeId))
            ->orderBy('sale_date')
            ->get();

        // خرائط مساعدة
        $salesById = $sales->keyBy('id');

        // أرصدة المتبقي لكل مجموعة
        $debts = $sales->groupBy(fn ($s) => $s->sale_group_id ?? $s->id)
            ->map(function ($g) {
                $total = (float) $g->sum('usd_sell');
                $paid  = (float) $g->sum('amount_paid');
                $coll  = (float) $g->flatMap->collections->sum('amount');
                $rem   = round($total - $paid - $coll, 2);
                $sale  = $g->first();
                return $rem > 0 ? (object) ['sale_id' => $sale->id, 'remaining' => $rem] : null;
            })
            ->filter()
            ->values();

        $applied = 0.0;

        foreach ($debts as $d) {
            if ($available <= 0) break;

            $pay = min($available, $d->remaining);
            if ($pay <= 0) continue;

            // قيّد تحصيل من المحفظة
            $collection = Collection::create([
                'agency_id'    => $customer->agency_id,
                'sale_id'      => $d->sale_id,
                'amount'       => $pay,
                'payment_date' => Carbon::now()->toDateString(),
                'method'       => 'wallet',
                'note'         => 'تحصيل تلقائي من رصيد العميل',
                'user_id'      => $onlyEmployeeId ?? auth()->id(),
            ]);

            // حقول المُحصّل
            $collection->forceFill([
                'collector_user_id' => $collectorUserId,
                'collector_method'  => $collectorMethod,
            ])->save();

            // عمولة المُحصّل + خصمها من البائع أو تسجيل دين عليه
            $sale = $salesById[$d->sale_id] ?? null;
            if ($sale && $collectorUserId && $collectorMethod) {
$ratePct    = $this->collectorRatePct($sale->agency_id, $collectorMethod, (int)$collectorUserId);
                \Log::info('collector.commission.debug', [
                    'sale_id'          => $sale->id,
                    'sellerUserId'     => (int) $sale->user_id,
                    'collectorUserId'  => (int) $collectorUserId,
                    'collectorMethod'  => $collectorMethod,
                    'ratePct'          => $ratePct,
                    'applied_pay'      => $pay,
                ]);

                $commission = round($pay * ($ratePct / 100), 2);
                if ($commission > 0) {
                    app(\App\Services\EmployeeWalletService::class)->transferCollectorCommission(
                        amount: $commission,
                        sellerUserId: (int) $sale->user_id,          // يخصم من البائع أو يسجّل دين
                        collectorUserId: (int) $collectorUserId,     // يُودع للمحصّل
                        refBase: 'col:' . $collection->id,
                        note: 'عمولة تحصيل على قيد #' . $collection->id
                    );
                }
            }

            // سحب من محفظة العميل
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


    // App/Services/AutoSettlementService.php
 private function collectorRatePct(int $agencyId, ?int $collectorMethod, ?int $collectorUserId = null): float
{
    if (!$collectorMethod) return 0.0;

    $profile = \App\Models\CommissionProfile::where('agency_id', $agencyId)
        ->where('is_active', true)->first();
    if (!$profile) return 0.0;

    // 1) جرّب Override مخصّص للمحصّل والطريقة
    $ov = \DB::table('commission_collector_overrides')
        ->where('profile_id', $profile->id)
        ->when($collectorUserId, fn($q)=>$q->where('user_id', $collectorUserId))
        ->where('method', $collectorMethod)
        ->first();

    // 2) وإلا خذ القاعدة العامة للطريقة
    $rule = $ov ?: \DB::table('commission_collector_rules')
        ->where('profile_id', $profile->id)
        ->where('method', $collectorMethod)
        ->first();

    if (!$rule) return 0.0;

    // value مع type=percent:
    // - لو القيمة <= 1 نعتبرها كسريّة (0.01 = 1%)
    // - لو > 1 نعتبرها كنسبة مباشرة (5 = 5%)
    if ($rule->type === 'percent') {
        $v = (float)$rule->value;
        return $v <= 1 ? $v * 100.0 : $v;
    }

    // أنواع أخرى (fixed…): حالياً نتجاهلها ونرجع 0
    return 0.0;
}


}
