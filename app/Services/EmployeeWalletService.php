<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
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

        $ref  = \Carbon\Carbon::parse($sale->sale_date ?: $sale->created_at);
        $year = (int)$ref->year; 
        $month = (int)$ref->month;

        // لو عنده Override للشهر استخدمه، وإلا ارجع لملف البروفايل
        $override = \App\Models\EmployeeMonthlyTarget::where('user_id', $sale->user_id)
            ->where('year', $year)->where('month', $month)
            ->value('override_rate');

        if ($override !== null) {
            $ratePct = (float)$override;
        } else {
            $ratePct = (float) \App\Models\CommissionProfile::where('agency_id', $sale->agency_id)
                ->where('is_active', true)->value('employee_rate') ?? 0;
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
$adjRef = $ref.':adjust:'.now()->format('YmdHis');

if ($diff > 0) {
    $this->post($locked, 'commission_adjust', $diff, $adjRef, 'زيادة عمولة متوقعة');
} else {
    $this->post($locked, 'withdraw', abs($diff), $adjRef, 'تخفيض عمولة متوقعة');
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

public function transferCollectorCommission(
    float $amount, int $sellerUserId, int $collectorUserId, string $refBase, string $note = ''
): void {
    if ($amount <= 0) return;

    DB::transaction(function () use ($amount, $sellerUserId, $collectorUserId, $refBase, $note) {
        $seller    = $this->ensureWallet($sellerUserId);
        $collector = $this->ensureWallet($collectorUserId);

        $sellerLocked    = EmployeeWallet::lockForUpdate()->find($seller->id);
        $collectorLocked = ($collector->id === $sellerLocked->id)
            ? $sellerLocked
            : EmployeeWallet::lockForUpdate()->find($collector->id);

        $doWithdraw = function () use ($sellerLocked, $amount, $refBase) {
            if (($sellerLocked->balance ?? 0) >= $amount) {
                $this->post($sellerLocked, 'withdraw', $amount, $refBase.':seller', 'خصم عمولة التحصيل');
            } else {
                $this->post($sellerLocked, 'sale_debt', $amount, $refBase.':debt', 'دين عمولة التحصيل');
            }
        };

        $doDeposit = function () use ($collectorLocked, $amount, $refBase, $note) {
            $this->post($collectorLocked, 'commission_collected', $amount, $refBase, $note ?: 'عمولة تحصيل');
        };

        // نفس الموظف؟ اعكس الترتيب ليتضح في الكشف: خصم ثم إيداع
        if ($sellerUserId === $collectorUserId) {
            $doWithdraw();
            $doDeposit();
        } else {
            // مختلفان: أودِع للمحصّل ثم خصم من البائع
            $doDeposit();
            $doWithdraw();
        }
    });
}



    // NEW: إعادة حساب عمولات شهر كامل لموظف
    public function recalcMonthForUser(int $userId, int $year, int $month): void
    {
        $sales = \App\Models\Sale::query()
            ->where('user_id', $userId)
            ->where('status', '!=', 'Void')
            ->whereYear('sale_date', $year)
            ->whereMonth('sale_date', $month)
            ->orderBy('sale_date')->orderBy('id')
            ->get();

        foreach ($sales as $s) {
            // سيضبط الفروقات لكل عملية (زيادة/سحب) ويُحدّث الحركة الأساسية
            $this->upsertExpectedCommission($s);
        }
    }


private function computeEmployeeCommissionOverTarget(\App\Models\Sale $sale): float
{
    $user = \App\Models\User::findOrFail($sale->user_id);
    $ref  = \Carbon\Carbon::parse($sale->sale_date ?: $sale->created_at);
    $year = (int)$ref->year; $month = (int)$ref->month;

    // هدف الشهر
    $target = (float) (\App\Models\EmployeeMonthlyTarget::where('user_id',$user->id)
                ->where('year',$year)->where('month',$month)->value('main_target')
            ?? $user->main_target ?? 0);

    // Override الشهري إن وجد، وإلا نسبة بروفايل الوكالة
    $override = \App\Models\EmployeeMonthlyTarget::where('user_id',$sale->user_id)
        ->where('year',$year)->where('month',$month)->value('override_rate');

    $ratePct = ($override !== null)
        ? (float)$override
        : (float)(\App\Models\CommissionProfile::where('agency_id',$sale->agency_id)
            ->where('is_active',true)->value('employee_rate') ?? 0);
    $rate = $ratePct / 100.0;




    $mStart = $ref->copy()->startOfMonth()->toDateString();
    $mEnd   = $ref->copy()->endOfMonth()->toDateString();

    $base = \App\Models\Sale::query()
        ->where('agency_id', $sale->agency_id)
        ->where('user_id',   $sale->user_id)
        ->where('status','!=','Void')
        ->whereBetween('sale_date', [$mStart, $mEnd]);

    $saleProfit = max((float)($sale->sale_profit ?? (($sale->usd_sell ?? 0) - ($sale->usd_buy ?? 0))), 0);

    $beforeSum = (float) (clone $base)
        ->where(function($q) use ($sale) {
            $q->where('sale_date','<',$sale->sale_date)
              ->orWhere(function($qq) use ($sale){
                  $qq->where('sale_date','=',$sale->sale_date)->where('id','<',$sale->id);
              });
        })
        ->sum(\DB::raw('COALESCE(sale_profit, (usd_sell - usd_buy))'));
    $beforeSum = max($beforeSum, 0);

    $afterSum = $beforeSum + $saleProfit;

    $excessBefore = max(0, $beforeSum - $target);
    $excessAfter  = max(0, $afterSum  - $target);
    $incremental  = max(0, $excessAfter - $excessBefore);

    if ($target <= 0) {
        $incremental = $saleProfit;
    }

    return round($incremental * $rate, 2);
}



}
