<?php

namespace App\Services;

use App\Models\{EmployeeWallet, EmployeeWalletTransaction, Sale, CommissionProfile};
use Illuminate\Support\Facades\DB;
use App\Models\CommissionEmployeeRateOverride;

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
            $amount = $this->computeEmployeeCommission($sale);
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

            $newAmount = $this->computeEmployeeCommission($sale);

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
            $amount = $this->computeEmployeeCommission($sale);
            if ($amount > 0) {
                $this->post($wallet, 'sale_debt', $amount, $ref, "دين عمولة لعملية غير محصّلة #{$sale->id}");
            }
        }
    }

    /** عند اكتمال التحصيل لاحقًا: يفك الدين ثم يُثبت العمولة */
    public function releaseDebtAndPostCommission(Sale $sale): void
    {
        $wallet = $this->ensureWallet($sale->user_id);
        $amount = $this->computeEmployeeCommission($sale);
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

}
