<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\{EmployeeWalletService, CommissionPolicyService}; // CommissionPolicyService: تحسب نسبة الموظف الفعالة من البروفايل/الاستثناءات

class SaleObserver
{
    public function created(Sale $sale) { $this->tryPost($sale); }
    public function updated(Sale $sale) { $this->tryPost($sale); }

    protected function tryPost(Sale $sale): void
    {
        // اعتبر البيع “مُحصّل بالكامل”:
        $totalPaid = (float)($sale->amount_paid ?? 0) + (float)$sale->collections()->sum('amount');
        $isFullyCollected = $totalPaid + 0.01 >= (float)($sale->usd_sell ?? 0);

        // احسب صافي الربح
        $net = max((float)$sale->usd_sell - (float)$sale->usd_buy, 0);

        // احسب نسبة الموظف الفعالة
        $ratePct = app(CommissionPolicyService::class)->employeeRateFor($sale->user_id, $sale->agency_id);
        $empCommission = round($net * ((float)$ratePct/100), 2);

        // ترحيل عمولة عند الاكتمال ولم تُرحّل سابقاً
        if ($isFullyCollected && $empCommission > 0 && !$sale->employee_commission_posted_at) {
            app(EmployeeWalletService::class)->postCommissionEarned($sale, $empCommission);
        }

        // دين الموظف: لو تجاوزت المهلة ولم يُحصّل بعد ولم يُقيّد الدين
        $policy = app(CommissionPolicyService::class)->debtPolicy($sale->agency_id); // days, behavior
        if (!$isFullyCollected && !$sale->employee_debt_charged_at) {
            $days = (int)($policy['days'] ?? 0);
            if ($days > 0 && now()->diffInDays($sale->sale_date) >= $days) {
                // في سلوك "خصم حتى السداد" نقيد الدين = عمولة الموظف المتوقعة
                if (($policy['behavior'] ?? 'deduct_commission_until_paid') === 'deduct_commission_until_paid' && $empCommission > 0) {
                    app(EmployeeWalletService::class)->postDebtCharge($sale, $empCommission);
                }
                // في "hold_commission" بإمكانك فقط عدم ترحيل العمولة (أو تسجل commission_held كسجل غير مؤثر)
            }
        }

        // عند التحصيل لاحقاً بعد تقييد الدين → سداد الدين
        if ($isFullyCollected && $sale->employee_debt_charged_at && !$sale->employee_debt_cleared_at && $empCommission > 0) {
            app(EmployeeWalletService::class)->postDebtPayment($sale, $empCommission);
        }
    }
}
