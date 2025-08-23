<?php

namespace App\Services;

use App\Models\{Sale, Customer, Wallet, Collection, WalletTransaction};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerCreditService
{   
     // طريقة التحصيل الافتراضية لتحصيلات "المحفظة"
    // 3 = "عبر الموظف مباشرة" حسب المصفوفة في CommissionPolicies
    private const WALLET_METHOD = 3;
    public function computeAvailableCredit(int $customerId, int $agencyId): float
    {
        $sales = Sale::with('collections')
            ->where('agency_id', $agencyId)
            ->where('customer_id', $customerId)
            ->get();

        $byGroup = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

        $rawCredit = 0.0;
        foreach ($byGroup as $group) {
           // دالة صغيرة لحساب إجمالي الصفقة الفعلي حسب الحالة
$getEffectiveTotal = function ($s): float {
    $status = mb_strtolower(trim((string) $s->status));

    // إلغاء: لا تأثير على الإجمالي
    if ($status === 'void' || str_contains($status, 'cancel')) {
        return 0.0;
    }

    // Refund Full/Partial: اعتبر الصفقة “رصيداً سالباً” يخصم من المجموعة
    if (str_contains($status, 'refund')) {
        // إن كان لديك عمود refund_amount استخدمه وإلا خذ قيمة البيع كقيمة استرداد
        $refund = (float) ($s->refund_amount ?? 0);
        if ($refund <= 0) {
            $refund = abs((float) ($s->usd_sell ?? 0)); // usd_sell يكون سالباً في نمطك
        }
        return -1 * $refund; // يخصم من صافي المجموعة
    }

    // الصفقة العادية/المعاد إصدارها
    return (float) ($s->invoice_total_true ?? $s->usd_sell ?? 0);
};

$remaining = $group->sum(function ($s) use ($getEffectiveTotal) {
    $totalDue  = $getEffectiveTotal($s);

    // لا تسمح بأي مبالغ سالبة تُحسب كمدفوعات
    $paid      = max(0.0, (float) ($s->amount_paid ?? 0));
    $collected = max(0.0, (float) $s->collections->sum('amount'));

    return $totalDue - $paid - $collected;
});


// إن صار سالباً فهو رصيد لصالح العميل
if ($remaining < 0) {
    $rawCredit += abs($remaining);
}

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

// لا نسدد من المحفظة لصفقة ملغاة/مستردة
$status = mb_strtolower(trim((string) $sale->status));
if ($status === 'void' || str_contains($status, 'cancel') || (str_contains($status, 'refund') && str_contains($status, 'full'))) {
    return;
}

// إجمالي فعلي حسب الحالة
$totalDue = (float) ($sale->invoice_total_true ?? $sale->usd_sell ?? 0);
$refundAmount = (float) ($sale->refund_amount ?? 0);
if ($refundAmount > 0) $totalDue = max(0.0, $totalDue - $refundAmount);

if ($totalDue <= 0) return;

$totalPaid = max(0.0, (float) ($sale->amount_paid ?? 0));
$collected = max(0.0, (float) $sale->collections()->sum('amount'));
$remaining = max(0.0, $totalDue - $totalPaid - $collected);

    if ($remaining <= 0) return;

    DB::transaction(function () use ($sale, $remaining) {
        // احصل على المحفظة عبر علاقة العميل (بدون agency_id)
        $customer = Customer::findOrFail($sale->customer_id);
        $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

        $available = (float) $wallet->balance;
        if ($available <= 0) return;

       $use = min($remaining, $available);

$gid = (string)($sale->sale_group_id ?: $sale->id);

WalletTransaction::create([
    'wallet_id'        => $wallet->id,
    'type'             => 'withdraw',
    'amount'           => $use,
    'running_balance'  => $wallet->balance - $use,
    'reference'        => 'sale:'.$sale->id.'|group:'.$gid,
    'note'             => 'سداد تلقائي لمجموعة #'.$gid.' (سجل #'.$sale->id.')',
    'performed_by_name'=> Auth::user()->name ?? 'system',
]);

$wallet->decrement('balance', $use);

Collection::create([
    'sale_id'           => $sale->id,
    'customer_id'       => $sale->customer_id,
    'agency_id'         => $sale->agency_id,
    'amount'            => $use,
    'method'            => 'wallet',
    'collector_method'  => self::WALLET_METHOD,
    'collector_user_id' => Auth::id(),
    'note'              => 'سداد محفظة لمجموعة #'.$gid.' (سجل #'.$sale->id.')',
    'payment_date'      => now()->format('Y-m-d'),
]);


    });
}


// CustomerCreditService.php
public function autoPayAllFromWallet(Customer $customer): float
{
    $totalApplied = 0.0;

    DB::transaction(function () use ($customer, &$totalApplied) {

        // اقفل المحفظة ثم اجلب الرصيد
        $wallet = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);
        $balance = (float) $wallet->balance;
        if ($balance <= 0) return;

        // اجلب المبيعات غير المسددة بالأقدمية
        $sales = Sale::with('collections')
            ->where('customer_id', $customer->id)
          //  ->whereIn('payment_method', ['part','all'])
            ->orderBy('id')  // بدّلها بتاريخ البيع إن وُجد عمود
            ->get();
foreach ($sales as $sale) {
    $gid = (string)($sale->sale_group_id ?: $sale->id);

            $totalPaid = (float) ($sale->amount_paid ?? 0);
            $collected = (float) $sale->collections->sum('amount');
            $totalDue  = (float) ($sale->invoice_total_true ?? $sale->usd_sell ?? 0);
            $remaining = max(0.0, $totalDue - $totalPaid - $collected);
            if ($remaining <= 0) continue;

            $use = min($remaining, $balance);
            if ($use <= 0) continue;

            // حركة سحب من المحفظة
            WalletTransaction::create([
                'wallet_id'        => $wallet->id,
                'type'             => 'withdraw',
                'amount'           => $use,
                'running_balance'  => $balance - $use,
                'reference'        => 'sale:'.$sale->id.'|group:'.$gid,
                'note'             => 'سداد محفظة لمجموعة #'.$gid.' (سجل #'.$sale->id.')',
                'performed_by_name'=> auth()->user()->name ?? 'system',
            ]);

            // حدث الرصيد
            $balance -= $use;
            $wallet->balance = $balance;
            $wallet->save();

            // قيد تحصيل يظهر في تقارير التحصيل
            Collection::create([
                'sale_id'           => $sale->id,
                'customer_id'       => $sale->customer_id,
                'agency_id'         => $sale->agency_id,
                'amount'            => $use,
                'method'            => 'wallet',
                'collector_method'  => self::WALLET_METHOD,
                'collector_user_id' => auth()->id(),
                'note' => 'سداد محفظة لمجموعة #'.$gid.' (سجل #'.$sale->id.')',
                'payment_date'      => now()->format('Y-m-d'),
            ]);


            $totalApplied += $use;
        }
    });

    return round($totalApplied, 2);
}

// app/Services/CustomerCreditService.php

public function syncCustomerCommission(Sale $sale): void
{
    if (!$sale->customer_id) return;

    DB::transaction(function () use ($sale) {
        $groupId = (string)($sale->sale_group_id ?: $sale->id);
        $ref     = 'commission:group:' . $groupId;

        // اقفل المحفظة
        $customer = Customer::findOrFail($sale->customer_id);
        $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

        // اجلب كل عمليات نفس المجموعة
        $groupSales = Sale::where(function ($q) use ($groupId) {
                $q->where('sale_group_id', $groupId)
                  ->orWhere('id', $groupId); // دعم سجل قديم بلا sale_group_id
            })
            ->where('customer_id', $sale->customer_id)
            ->get();

        // هل يوجد استرداد كلي في أي سجل داخل المجموعة؟
        $hasFullRefund = $groupSales->contains(function ($s) {
            return strcasecmp((string)$s->status, 'Refund-Full') === 0;
        });

        // الصافي المقيد سابقاً لمرجع العمولة لهذه المجموعة
        $deposited = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference', $ref)
            ->where('type', 'deposit')
            ->sum('amount');

        $withdrawn = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference', $ref)
            ->where('type', 'withdraw')
            ->sum('amount');

        $net = (float)$deposited - (float)$withdrawn;

        if ($hasFullRefund) {
            // صفّر العمولة (اسحب الصافي إن وُجد)
            if ($net > 0) {
                WalletTransaction::create([
                    'wallet_id'        => $wallet->id,
                    'type'             => 'withdraw',
                    'amount'           => $net,
                    'running_balance'  => $wallet->balance - $net,
                    'reference'        => $ref,
                    'note'             => 'عكس عمولة المجموعة بسبب استرداد كلي',
                    'performed_by_name'=> auth()->user()->name ?? 'system',
                ]);
                $wallet->decrement('balance', $net);
            }
            return;
        }

        // لا تُسجل عمولة ثانية إن كان هناك قيد سابق لنفس المجموعة
        $commission = (float)($sale->commission ?? 0);
        $customerHasCommission = (bool)($customer->has_commission ?? false);

        if ($net <= 0 && $commission > 0 && $customerHasCommission) {
            // قيّد العمولة مرة واحدة للمجموعة
            WalletTransaction::create([
                'wallet_id'        => $wallet->id,
                'type'             => 'deposit',
                'amount'           => $commission,
                'running_balance'  => $wallet->balance + $commission,
                'reference'        => $ref,
                'note'             => 'عمولة عميل للمجموعة #' . $groupId,
                'performed_by_name'=> auth()->user()->name ?? 'system',
            ]);
            $wallet->increment('balance', $commission);
        }
    });
}


}
