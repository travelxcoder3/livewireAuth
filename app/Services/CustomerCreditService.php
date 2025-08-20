<?php

namespace App\Services;

use App\Models\{Sale, Customer, Wallet, Collection, WalletTransaction};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerCreditService
{
    public function computeAvailableCredit(int $customerId, int $agencyId): float
    {
        $sales = Sale::with('collections')
            ->where('agency_id', $agencyId)
            ->where('customer_id', $customerId)
            ->get();

        $byGroup = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

        $rawCredit = 0.0;
        foreach ($byGroup as $group) {
            $remaining = $group->sum(fn($s) =>
                ($s->usd_sell ?? 0) - ($s->amount_paid ?? 0) - $s->collections->sum('amount')
            );
            if ($remaining < 0) $rawCredit += abs($remaining);
        }

        $usedFromCollections = Collection::whereHas('sale', fn($q) =>
                $q->where('customer_id', $customerId)
            )
            ->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')
            ->sum('amount');

        $usedFromWallet = WalletTransaction::whereHas('wallet', fn($q) =>
                $q->where('customer_id', $customerId)
            )
            ->where('type', 'deposit')
            ->whereIn('reference', ['employee-collections','sales-auto'])
            ->sum('amount');

        return max(0, $rawCredit - $usedFromCollections - $usedFromWallet);
    }

    public function autoDepositToWallet(int $customerId, int $agencyId, string $who = 'sales-auto'): ?float
    {
        $amount = round($this->computeAvailableCredit($customerId, $agencyId), 2);
        if ($amount <= 0) return null;

        DB::transaction(function () use ($customerId, $agencyId, $amount, $who) {
            $customer = Customer::where('agency_id', $agencyId)->findOrFail($customerId);
            $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

            $wallet->balance = bcadd($wallet->balance, $amount, 2);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id'         => $wallet->id,
                'type'              => 'deposit',
                'amount'            => $amount,
                'running_balance'   => $wallet->balance,
                'reference'         => $who,
                'note'              => 'تسوية تلقائية من المبيعات',
                'performed_by_name' => Auth::user()->name ?? 'system',
            ]);
        });

        return $amount;
    }

    /** خصم تلقائي من رصيد العميل لتغطية المتبقي في البيع الجزئي */
   public function autoPayFromWallet(Sale $sale): void
{
    if (!$sale->customer_id) return;
    if ($sale->usd_sell <= 0) return;
if (!in_array($sale->payment_method, ['part','all'])) return;

    $totalPaid = (float) ($sale->amount_paid ?? 0);
    $collected = (float) $sale->collections()->sum('amount');
    $remaining = max(0.0, (float)$sale->usd_sell - $totalPaid - $collected);
    if ($remaining <= 0) return;

    DB::transaction(function () use ($sale, $remaining) {
        // احصل على المحفظة عبر علاقة العميل (بدون agency_id)
        $customer = Customer::findOrFail($sale->customer_id);
        $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

        $available = (float) $wallet->balance;
        if ($available <= 0) return;

       $use = min($remaining, $available);

// سجّل حركة سحب من المحفظة
WalletTransaction::create([
    'wallet_id'         => $wallet->id,
    'type'              => 'withdraw',          // بدلاً من 'debit'
    'amount'            => $use,                // مبلغ موجب
    'running_balance'   => $wallet->balance - $use,
    'reference'         => 'sale:' . $sale->id,
    'note'              => 'سداد تلقائي لبيع جزئي #' . $sale->id,
    'performed_by_name' => Auth::user()->name ?? 'system',
]);

// ثم حدّث رصيد المحفظة
$wallet->decrement('balance', $use);

        // قيد تحصيل للعملية ليظهر في الإجماليات
        Collection::create([
            'sale_id'           => $sale->id,
            'customer_id'       => $sale->customer_id,
            'agency_id'         => $sale->agency_id,  // اتركه إذا كان موجودًا بجدول التحصيلات عندك
            'amount'            => $use,
            'method'            => 'wallet',
            'collector_user_id' => Auth::id(),
            'note'              => 'سداد من رصيد العميل تلقائياً',
    'payment_date'      => now()->format('Y-m-d'), // لو العمود DATE
        ]);
    });
}

}
