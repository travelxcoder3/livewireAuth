<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\CommissionEmployeeRateOverride;
use App\Models\{EmployeeWallet, EmployeeWalletTransaction, Sale, CommissionProfile, User};
class EmployeeWalletService
{
    public function ensureWallet(int $userId): EmployeeWallet
    {
        return EmployeeWallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'status' => 'active']
        );
    }

    public function post(EmployeeWallet $wallet, string $type, float $amount,
                         ?string $reference = null, ?string $note = null,
                         ?string $performedBy = 'system'): EmployeeWalletTransaction
    {
        return DB::transaction(function () use ($wallet, $type, $amount, $reference, $note, $performedBy) {
            $locked = EmployeeWallet::lockForUpdate()->find($wallet->id);

            $newBalance = match ($type) {
                'withdraw', 'sale_debt' => $locked->balance - $amount,
                default                  => $locked->balance + $amount,
            };

            $tx = EmployeeWalletTransaction::create([
                'wallet_id'        => $locked->id,
                'type'             => $type,
                'amount'           => $amount,
                'running_balance'  => $newBalance,
                'reference'        => $reference,
                'note'             => $note,
                'performed_by_name'=> $performedBy,
            ]);

            $locked->update(['balance' => $newBalance]);
            return $tx;
        });
    }

    /** % عمولة الموظف = employee_rate من صافي الربح */
    public function computeEmployeeCommission(Sale $sale): float
    {
        $profile = CommissionProfile::where('agency_id', $sale->agency_id)
            ->where('is_active', true)->first();

        $ratePct = 0.0;
        if ($profile) {
            $override = CommissionEmployeeRateOverride::where('profile_id', $profile->id)
                ->where('user_id', $sale->user_id)
                ->value('rate');
            $ratePct = $override ?? (float)($profile->employee_rate ?? 0);
        }

        $profit = (float)($sale->sale_profit ?? (($sale->usd_sell ?? 0) - ($sale->usd_buy ?? 0)));
        return max(round($profit * ($ratePct / 100), 2), 0.0);
    }

     /** عمولة متوقعة تُقيد فور إنشاء البيع (إدراج مرّة واحدة) */
    public function postExpectedCommission(Sale $sale): void
    {
        $wallet = $this->ensureWallet($sale->user_id);
        $ref = "sale:{$sale->id}:expected";

        $exists = EmployeeWalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference', $ref)->exists();

        if (!$exists) {
            $amount = $this->computeEmployeeCommissionOverTarget($sale);
            if ($amount > 0) {
                $this->post($wallet, 'commission_expected', $amount, $ref, "عمولة متوقعة لعملية #{$sale->id}");
            }
        }
    }
    /** تعديل العمولة المتوقعة إذا تغيّر ربح العملية لاحقًا */
    public function upsertExpectedCommission(Sale $sale): void
    {
        $wallet = $this->ensureWallet($sale->user_id);
        $ref = "sale:{$sale->id}:expected";

        DB::transaction(function() use ($wallet, $ref, $sale) {
            $locked = EmployeeWallet::lockForUpdate()->find($wallet->id);
            $current = EmployeeWalletTransaction::where('wallet_id', $locked->id)
                ->where('reference', $ref)->first();

            $newAmount = $this->computeEmployeeCommissionOverTarget($sale);

            if (!$current) {
                if ($newAmount > 0) {
                    $this->post($locked, 'commission_expected', $newAmount, $ref, "عمولة متوقعة لعملية #{$sale->id}");
                }
                return;
            }

            $diff = round($newAmount - (float)$current->amount, 2);
            if (abs($diff) >= 0.01) {
                if ($diff > 0) {
                    $this->post($locked, 'commission_adjust', $diff, $ref.':adjust', 'زيادة عمولة متوقعة');
                } else {
                    $this->post($locked, 'withdraw', abs($diff), $ref.':adjust', 'تخفيض عمولة متوقعة');
                }
                $current->update(['amount' => $newAmount]);
            }
        });
    }
    /** عندما تكتمل تحصيلات عملية البيع */
    public function postCommissionEarned(Sale $sale): void
    {
        // مُعطّل: العمولات تُدار عبر الإقفال الشهري (expected accrual)
    }


    /** يقيّد دينًا على الموظّف بعد إنقضاء days_to_debt وعدم اكتمال التحصيل */
    public function postSaleDebt(Sale $sale): void
    {
        $wallet = $this->ensureWallet($sale->user_id);
        $ref = "sale:{$sale->id}:debt";
        $exists = EmployeeWalletTransaction::where('wallet_id',$wallet->id)->where('reference',$ref)->exists();
        if (!$exists) {
            $amount = $this->computeEmployeeCommissionOverTarget($sale);
            if ($amount > 0) {
                $this->post($wallet, 'sale_debt', $amount, $ref, "دين عمولة لعملية غير محصّلة #{$sale->id}");
            }
        }
    }

    /** عند اكتمال التحصيل لاحقًا: يفك الدين ثم يُثبت العمولة */
    public function releaseDebtAndPostCommission(Sale $sale): void
    {
        $wallet = $this->ensureWallet($sale->user_id);
        $amount = $this->computeEmployeeCommissionOverTarget($sale);
        if ($amount <= 0) return;

        // لا نفك الدين إلا إذا اكتمل التحصيل فعلاً
        $required  = (float)($sale->usd_sell ?? 0);
        $collected = (float)($sale->amount_paid ?? 0) + (float)$sale->collections()->sum('amount');
        if ($collected + 0.01 < $required) return;

        $hadDebt = EmployeeWalletTransaction::where('wallet_id',$wallet->id)
            ->where('reference',"sale:{$sale->id}:debt")->exists();

        if ($hadDebt) {
            $this->post($wallet, 'debt_release', $amount,
                "sale:{$sale->id}:debt_release", "فك دين عملية #{$sale->id}");
        }
    }

    // App/Services/EmployeeWalletService.php
    public function transferCollectorCommission(
        float $amount, int $sellerUserId, int $collectorUserId, string $refBase, string $note = ''
    ): void {
        if ($amount <= 0) return;

        DB::transaction(function () use ($amount, $sellerUserId, $collectorUserId, $refBase, $note) {
            $seller    = $this->ensureWallet($sellerUserId);
            $collector = $this->ensureWallet($collectorUserId);

            // إيداع للمحصّل
            $this->post($collector, 'commission_collected', $amount, $refBase, $note ?: 'عمولة تحصيل');

            // خصم من البائع أو تسجيل دين
            $sellerLocked = EmployeeWallet::lockForUpdate()->find($seller->id);
            if (($sellerLocked->balance ?? 0) >= $amount) {
                $this->post($sellerLocked, 'withdraw', $amount, $refBase.':seller', 'خصم عمولة التحصيل');
            } else {
                $this->post($sellerLocked, 'sale_debt', $amount, $refBase.':debt', 'دين عمولة التحصيل');
            }
        });
    }


    // App\Services\EmployeeWalletService.php


private function computeEmployeeCommissionOverTarget(Sale $sale): float
{
    // نسبة الموظف
    $profile = CommissionProfile::where('agency_id', $sale->agency_id)
        ->where('is_active', true)->first();

    $ratePct = 0.0;
    if ($profile) {
        $override = CommissionEmployeeRateOverride::where('profile_id', $profile->id)
            ->where('user_id', $sale->user_id)
            ->value('rate');
        $ratePct = (float) ($override ?? ($profile->employee_rate ?? 0));
    }
    $rate = $ratePct / 100.0;

    // هدف الموظف الشهري
    $target = (float) (User::where('id', $sale->user_id)->value('main_target') ?? 0);

    // الشهر المرجعي = شهر الإنشاء
    $refDate = $sale->created_at instanceof \Carbon\Carbon
        ? $sale->created_at
        : \Carbon\Carbon::parse($sale->created_at);
    $mStart  = $refDate->copy()->startOfMonth()->toDateString();
    $mEnd    = $refDate->copy()->endOfMonth()->toDateString();

    // ربح العملية
    $saleProfit = max((float) ($sale->sale_profit ?? (($sale->usd_sell ?? 0) - ($sale->usd_buy ?? 0))), 0);

    // كل مبيعات نفس الشهر بالإنشاء فقط
    $base = Sale::query()
        ->where('agency_id', $sale->agency_id)
        ->where('user_id',   $sale->user_id)
        ->where('status','!=','Void')
        ->whereBetween(DB::raw('DATE(created_at)'), [$mStart, $mEnd]);

    // أرباح ما قبل هذه المبيعة (created_at ثم id)
    $beforeSum = (float) (clone $base)
        ->where(function($q) use ($sale) {
            $q->where('created_at','<',$sale->created_at)
              ->orWhere(function($qq) use ($sale){
                  $qq->where('created_at','=',$sale->created_at)
                     ->where('id','<',$sale->id);
              });
        })
        ->sum(DB::raw('COALESCE(sale_profit, (usd_sell - usd_buy))'));
    $beforeSum = max($beforeSum, 0);

    // بعد إضافة العملية
    $afterSum = $beforeSum + $saleProfit;

    // الجزء المتجاوز للهدف فقط
    $excessBefore = max(0, $beforeSum - $target);
    $excessAfter  = max(0, $afterSum  - $target);
    $incremental  = max(0, $excessAfter - $excessBefore);

    // إن لم يوجد هدف: عمولة كاملة من ربح العملية
    if ($target <= 0) {
        $incremental = $saleProfit;
    }

    return round($incremental * $rate, 2);
}


}
