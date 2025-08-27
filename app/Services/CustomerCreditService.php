<?php

namespace App\Services;

use App\Models\{Sale, Customer, Wallet, Collection, WalletTransaction};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // أعلى الملف

class CustomerCreditService
{   
     // طريقة التحصيل الافتراضية لتحصيلات "المحفظة"
    // 3 = "عبر الموظف مباشرة" حسب المصفوفة في CommissionPolicies
    private const WALLET_METHOD = 3;
    public function computeAvailableCredit(int $customerId, int $agencyId): float
    {
        $sales = Sale::withSum('collections','amount')
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
    // احسب كل التحصيلات بما فيها "wallet" حتى نعيد ما سُحب من المحفظة عند الاسترداد
$collected = max(0.0, (float) ($s->collections_sum_amount ?? 0));


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
                ->where(function($q){
                    $q->where('reference', 'employee-collections')
                    ->orWhere('reference', 'like', 'sales-auto%'); // يشمل sales-auto|group:...
                })
                ->sum('amount');


        return max(0, $rawCredit - $usedFromCollections - $usedFromWallet);
    }

public function autoDepositToWallet(int $customerId, int $agencyId, string $who = 'sales-auto'): ?float
{
    // إن كان استرداداً لمجموعة، أودِع "مبلغ الاسترداد الفعلي" لتلك المجموعة فقط
    if (str_starts_with($who, 'sales-auto|group:')) {
        $gid = Str::after($who, 'sales-auto|group:');
        $amount = $this->undepositedRefundForGroup($customerId, (string)$gid);
    } else {
        // غير ذلك: استخدم الصافي العام
        $amount = round($this->computeAvailableCredit($customerId, $agencyId), 2);
    }

    if ($amount <= 0) return null;

    DB::transaction(function () use ($customerId, $agencyId, $amount, $who) {
        $customer = \App\Models\Customer::where('agency_id', $agencyId)->findOrFail($customerId);
        $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

        $wallet->balance = bcadd($wallet->balance, $amount, 2);
        $wallet->save();

        \App\Models\WalletTransaction::create([
            'wallet_id'       => $wallet->id,
            'type'            => 'deposit',
            'amount'          => $amount,
            'running_balance' => $wallet->balance,
            'reference'       => $who,
            'note'            => 'تسوية استرداد',
            'performed_by_name'=> auth()->user()->name ?? 'system',
        ]);
    });

    // بعد الإيداع صفِّ الدين تلقائياً
    // بعد الإيداع: صفِّ فوراً المتبقي فقط واترك الفائض رصيداً
$customer = \App\Models\Customer::where('agency_id', $agencyId)->findOrFail($customerId);
$this->autoPayAllFromWallet($customer); // يستهلك حتى يصل الدين للصفر ويُبقي الباقي


    return $amount;
}



    /** خصم تلقائي من رصيد العميل لتغطية المتبقي في البيع الجزئي */
   public function autoPayFromWallet(Sale $sale): void
{
if (!$sale->customer_id) return;

// لا نسدد من المحفظة لصفقة ملغاة/مستردة
$status = mb_strtolower(trim((string) $sale->status));
if ($status === 'void' || str_contains($status, 'cancel') || str_contains($status, 'refund')) {
    return; // لا نسدد من المحفظة لأي استرداد (جزئي/كلي)
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
$customer = Customer::where('agency_id', $sale->agency_id)
    ->findOrFail($sale->customer_id);        $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

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

public function syncCustomerCommission(Sale $sale): void
{
    if (!$sale->customer_id) return;

    DB::transaction(function () use ($sale) {
        $groupId = (string)($sale->sale_group_id ?: $sale->id);
        $ref     = 'commission:group:' . $groupId;

        // اقفل المحفظة
$customer = Customer::where('agency_id', $sale->agency_id)
    ->findOrFail($sale->customer_id);        $wallet   = $customer->wallet()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);

        // كل سجلات نفس المجموعة
        $groupSales = Sale::where(function ($q) use ($groupId) {
                $q->where('sale_group_id', $groupId)
                  ->orWhere('id', $groupId);
            })
            ->where('customer_id', $sale->customer_id)
            ->orderBy('id') // التسلسل الزمني
            ->get();

      // بعد
        $hasFullRefund = $groupSales->contains(fn($s) => strcasecmp((string)$s->status, 'Refund-Full') === 0);
        $hasPartialRefund = $groupSales->contains(fn($s) => strcasecmp((string)$s->status, 'Refund-Partial') === 0);

        // احسب الصافي أولًا
        $deposited = (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference', $ref)->where('type', 'deposit')->sum('amount');
        $withdrawn = (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference', $ref)->where('type', 'withdraw')->sum('amount');
        $net = $deposited - $withdrawn;

     


        if ($hasFullRefund) {
            // صفّر الصافي إن وجد
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

        // أحدث سجل "فعّال" داخل المجموعة لتحديد العمولة المطلوبة
        $latestActive = $groupSales->last(function ($s) {
            $st = mb_strtolower((string)$s->status);
            return $st !== 'void'
                && !str_contains($st,'cancel')
                && !str_contains($st,'refund');
        });

        $desired = 0.0;
        if ($customer->has_commission && $latestActive) {
            $desired = (float) ($latestActive->commission ?? 0);
        }

        // اضبط الصافي الحالي ليطابق desired
        if ($desired > $net) {
            $diff = $desired - $net; // إيداع زيادة
            WalletTransaction::create([
                'wallet_id'        => $wallet->id,
                'type'             => 'deposit',
                'amount'           => $diff,
                'running_balance'  => $wallet->balance + $diff,
                'reference'        => $ref,
                'note'             => 'تعديل عمولة المجموعة #' . $groupId . ' (زيادة)',
                'performed_by_name'=> auth()->user()->name ?? 'system',
            ]);
            $wallet->increment('balance', $diff);
        } elseif ($desired < $net && $net > 0) {
            $diff = $net - $desired; // سحب فرق
            WalletTransaction::create([
                'wallet_id'        => $wallet->id,
                'type'             => 'withdraw',
                'amount'           => $diff,
                'running_balance'  => $wallet->balance - $diff,
                'reference'        => $ref,
                'note'             => 'تعديل عمولة المجموعة #' . $groupId . ' (خصم)',
                'performed_by_name'=> auth()->user()->name ?? 'system',
            ]);
            $wallet->decrement('balance', $diff);
        }
        // إن كان desired == net فلا إجراء
    });
}

private function undepositedRefundForGroup(int $customerId, string $gid): float
{
    $sales = \App\Models\Sale::where(function($q) use ($gid){
            $q->where('sale_group_id', $gid)->orWhere('id', $gid);
        })
        ->where('customer_id', $customerId)
        ->get();

    $refunds = $sales->filter(fn($s) => str_contains(mb_strtolower((string)$s->status), 'refund'))
        ->sum(function($s){
            $amt = (float)($s->refund_amount ?? 0);
            return $amt > 0 ? $amt : abs((float)($s->usd_sell ?? 0));
        });

    $already = \App\Models\WalletTransaction::whereHas('wallet', fn($q)=>
                    $q->where('customer_id', $customerId))
                ->where('type','deposit')
                ->where('reference', 'sales-auto|group:'.$gid)
                ->sum('amount');

    return max(0.0, round($refunds - $already, 2));
}


}
