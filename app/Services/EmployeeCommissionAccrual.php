<?php

namespace App\Services;

use App\Models\User;
use App\Models\Sale;
use App\Models\EmployeeWallet;
use App\Models\EmployeeWalletTransaction;
use App\Models\CommissionProfile;
use Illuminate\Support\Facades\DB;

class EmployeeCommissionAccrual
{

  public function expectedForMonth(User $user, int $year, int $month): float
{
    return $this->computeExpectedFor($user, $year, $month);
}



    public function syncWalletToExpected(\App\Models\User $user, int $year, int $month): void
{
    $svc    = app(\App\Services\EmployeeWalletService::class);
    $wallet = $svc->ensureWallet($user->id);

$expected = round($this->expectedForMonth($user, $year, $month), 2);
    $ref = "accrual:{$year}-{$month}";

    \DB::transaction(function () use ($wallet, $expected, $ref, $year, $month) {
        $locked = \App\Models\EmployeeWallet::lockForUpdate()->find($wallet->id);

        $existing = \App\Models\EmployeeWalletTransaction::where('wallet_id', $locked->id)
            ->where('reference', $ref)
            ->first();

        if (!$existing) {
            // إنشاء أول مرة
            $running = $locked->balance + $expected;
            \App\Models\EmployeeWalletTransaction::create([
                'wallet_id'        => $locked->id,
                'type'             => 'deposit',
                'amount'           => $expected,
                'running_balance'  => $running,
                'reference'        => $ref,
                'note'             => "تجميع عمولة متوقعة لشهر {$month}/{$year}",
                'performed_by_name'=> 'System (expected accrual)',
            ]);
            $locked->update(['balance' => $running]);
        } else {
          // نفس منطق شاشة المبيعات
[$totalProfit, $target, $rate] = $this->componentsFor($user, $year, $month);

// تقدير الربح السابق من حركة الشهر (حتى لا نخصم الهدف مرة ثانية)
$prevExpected = (float) $existing->amount;
$prevTotalEst = $rate > 0 ? ($prevExpected / $rate) + $target : 0.0;

// الزيادة فقط
$deltaProfit = max($totalProfit - $prevTotalEst, 0.0);
$diff        = round($deltaProfit * $rate, 2);

if ($diff >= 0.01) {
    $running = $locked->balance + $diff;
    \App\Models\EmployeeWalletTransaction::create([
        'wallet_id' => $locked->id,
        'type' => 'accrual_adjust',
        'amount' => $diff,
        'running_balance' => $running,
        'reference' => $ref . ':adjust',
        'note' => 'تسوية المتوقّع لنفس الشهر (بدون إعادة خصم الهدف)',
        'performed_by_name'=> 'System (expected accrual)',
    ]);
    $locked->update(['balance' => $running]);
    // زد المبلغ بدل استبداله ليمثّل مجموع المتوقّع حتى الآن
    $existing->update(['amount' => $prevExpected + $diff]);
}

        }
    });
}

 public function computeExpectedFor(User $user, int $year, int $month): float
{
    [$totalProfit, $target, $rate] = $this->componentsFor($user, $year, $month);
    return round(max(($totalProfit - $target) * $rate, 0), 2);
}

private function componentsFor(User $user, int $year, int $month): array
{
    $target = (float) (\App\Models\EmployeeMonthlyTarget::where('user_id',$user->id)
        ->where('year',$year)->where('month',$month)->value('main_target')
        ?? $user->main_target ?? 0);

    $override = \App\Models\EmployeeMonthlyTarget::where('user_id',$user->id)
        ->where('year',$year)->where('month',$month)->value('override_rate');

    $ratePct = ($override !== null)
        ? (float)$override
        : (float)(CommissionProfile::where('agency_id',$user->agency_id)
            ->where('is_active',true)->value('employee_rate') ?? 0);
    $rate = $ratePct / 100.0;

    $sales = Sale::where('user_id',$user->id)
        ->whereYear('sale_date',$year)->whereMonth('sale_date',$month)
        ->where('status','!=','Void')
        ->get(['id','usd_buy','usd_sell','sale_profit','sale_group_id','status']);

    $getProfit = fn($s) => is_numeric($s->sale_profit)
        ? (float)$s->sale_profit
        : (float)(($s->usd_sell ?? 0) - ($s->usd_buy ?? 0));

    $groups = $sales->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

    $totalProfit = 0.0;
    foreach ($groups as $g) {
        $hasRefund = $g->contains(fn($row) =>
            str_contains(mb_strtolower((string)($row->status ?? '')), 'refund')
            || (float)$row->usd_sell < 0
        );
        $totalProfit += $hasRefund
            ? (float) $g->filter(fn($row) => $getProfit($row) > 0)
                        ->sum(fn($row) => $getProfit($row))
            : (float) $g->sum(fn($row) => $getProfit($row));
    }

    return [$totalProfit, $target, $rate];
}


}
