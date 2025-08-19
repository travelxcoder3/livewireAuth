<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerCreditService
{
    // يحسب رصيد الشركة المتاح للعميل بعد طرح كل ما استُخدم/أودِع
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

        // ما استُخدم لتسديد عملاء آخرين
        $usedFromCollections = \App\Models\Collection::whereHas('sale', fn($q) =>
                $q->where('customer_id', $customerId)
            )
            ->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')
            ->sum('amount');

        // إيداعات المحفظة السابقة (من شاشة التحصيلات أو تلقائي من المبيعات)
        $usedFromWallet = WalletTransaction::whereHas('wallet', fn($q) =>
                $q->where('customer_id', $customerId)
            )
            ->where('type', 'deposit')
            ->whereIn('reference', ['employee-collections','sales-auto'])
            ->sum('amount');

        return max(0, $rawCredit - $usedFromCollections - $usedFromWallet);
    }

    // إيداع تلقائي وآمن (Idempotent)
    public function autoDepositToWallet(int $customerId, int $agencyId, string $who = 'sales-auto'): ?float
    {
        $amount = round($this->computeAvailableCredit($customerId, $agencyId), 2);
        if ($amount <= 0) return null;

        DB::transaction(function () use ($customerId, $agencyId, $amount, $who) {
            $customer = Customer::where('agency_id', $agencyId)->findOrFail($customerId);
            $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

            $wallet->balance = bcadd($wallet->balance, $amount, 2);
            $wallet->save();

            \App\Models\WalletTransaction::create([
                'wallet_id'         => $wallet->id,
                'type'              => 'deposit',
                'amount'            => $amount,
                'running_balance'   => $wallet->balance,
                'reference'         => $who, // 'sales-auto' أو 'employee-collections'
                'note'              => 'تسوية تلقائية من المبيعات',
                'performed_by_name' => Auth::user()->name ?? 'system',
            ]);
        });

        return $amount;
    }
}
