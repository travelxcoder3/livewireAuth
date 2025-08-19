<?php

namespace App\Services;

use App\Models\User;
use App\Models\Sale;
use App\Models\EmployeeWallet;
use App\Models\EmployeeWalletTransaction;
use App\Models\CommissionProfile;
use App\Models\CommissionEmployeeRateOverride;
use Illuminate\Support\Facades\DB;

class EmployeeCommissionAccrual
{
    public function expectedForMonth(User $user, int $year, int $month): float
    {
        $profile = CommissionProfile::where('agency_id', $user->agency_id)
            ->where('is_active', true)->first();

        $ratePct = $profile
            ? (float) (CommissionEmployeeRateOverride::where('profile_id', $profile->id)
                    ->where('user_id', $user->id)
                    ->value('rate') ?? $profile->employee_rate ?? 0)
            : 0;

        $rate   = $ratePct / 100.0;
        $target = (float) ($user->main_target ?? 0);

        $sales = Sale::where('user_id', $user->id)
            ->whereYear('sale_date', $year)
            ->whereMonth('sale_date', $month)
            ->get();

        $totalProfit = (float) $sales->sum('sale_profit');

        return round(max(($totalProfit - $target) * $rate, 0), 2);
    }

    public function syncWalletToExpected(\App\Models\User $user, int $year, int $month): void
{
    $svc    = app(\App\Services\EmployeeWalletService::class);
    $wallet = $svc->ensureWallet($user->id);

    $expected = round($this->computeExpectedFor($user, $year, $month), 2); // احسب المتوقع
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
            // موجودة: سوّي تسوية بالفارق بدل محاولة إدراج نفس المرجع
            $diff = round($expected - (float)$existing->amount, 2);
            if (abs($diff) >= 0.01) {
                $running = $locked->balance + $diff; // diff قد يكون + أو -
                \App\Models\EmployeeWalletTransaction::create([
                    'wallet_id'        => $locked->id,
                    'type'             => 'accrual_adjust',
                    'amount'           => abs($diff),
                    'running_balance'  => $running,
                    'reference'        => $ref . ':adjust',
                    'note'             => 'تسوية المتوقّع لنفس الشهر',
                    'performed_by_name'=> 'System (expected accrual)',
                ]);
                $locked->update(['balance' => $running]);
                // للتوثيق فقط: حدّث مبلغ الحركة الأساسية ليطابق المتوقّع الحالي
                $existing->update(['amount' => $expected]);
            }
        }
    });
}
    public function computeExpectedFor(User $user, int $year, int $month): float
    {
        // أرباح مبيعات الموظف في الشهر (استبعاد Void)
        $sales = Sale::where('user_id', $user->id)
            ->whereYear('sale_date', $year)
            ->whereMonth('sale_date', $month)
            ->where('status', '!=', 'Void')
            ->get(['usd_buy','usd_sell','sale_profit']);

        $totalProfit = $sales->sum(function ($s) {
            return is_numeric($s->sale_profit)
                ? (float) $s->sale_profit
                : (float) (($s->usd_sell ?? 0) - ($s->usd_buy ?? 0));
        });

        $target = (float) ($user->main_target ?? 0);

        // نسبة العمولة من البروفايل/الاستثناءات
        $profile = CommissionProfile::where('agency_id', $user->agency_id)
                    ->where('is_active', true)->first();

        $ratePct = 0.0;
        if ($profile) {
            $override = CommissionEmployeeRateOverride::where('profile_id', $profile->id)
                ->where('user_id', $user->id)
                ->value('rate');
            $ratePct = $override ?? (float) ($profile->employee_rate ?? 0);
        }

        $rate = $ratePct / 100.0;
        return round(max(($totalProfit - $target) * $rate, 0), 2);
    }
}
