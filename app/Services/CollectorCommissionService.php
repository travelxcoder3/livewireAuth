<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\{
    CommissionProfile, CommissionCollectorRule, CommissionCollectorOverride,
    Sale, Collection, User, EmployeeWallet, EmployeeWalletTransaction
};

class CollectorCommissionService
{
    public function awardForCollection(Collection $collection): void
    {
        // متطلبات
        if (!$collection->collector_user_id || !$collection->collector_method) return;

        $sale = Sale::with('agency')->findOrFail($collection->sale_id);
        $agencyId = $sale->agency_id;
        $employeeId = $collection->collector_user_id;
        $method = (int)$collection->collector_method;
        $collectedAmount = (float)$collection->amount;

        // حساب أساس الصافي/عمولة الموظف إن لزم
        $netMargin = max(((float)$sale->usd_sell) - ((float)$sale->usd_cost), 0);
        $employeeRate = app(\App\Services\CommissionPolicyService::class)
                            ->employeeRateFor($sale->user_id, $agencyId);
        $employeeCommission = round($netMargin * ($employeeRate/100), 2);

        // جلب قاعدة المُحصّل: override ثم العامة
        $profile = CommissionProfile::where('agency_id',$agencyId)->where('is_active',true)->first();
        if (!$profile) return;

        $rule = CommissionCollectorOverride::where('profile_id',$profile->id)
                    ->where('user_id',$employeeId)
                    ->where('method',$method)
                    ->first();

        if (!$rule) {
            $rule = CommissionCollectorRule::where('profile_id',$profile->id)
                    ->where('method',$method)->first();
        }
        if (!$rule) return;

        // تحديد الأساس
        $basisVal = match ($rule->basis) {
            'net_margin'          => $netMargin,
            'employee_commission' => $employeeCommission,
            default               => $collectedAmount, // collected_amount
        };

        // حساب عمولة المُحصّل
        $collectorCommission = ($rule->type === 'fixed')
            ? (float)$rule->value
            : round($basisVal * ((float)$rule->value / 100), 2);

        if ($collectorCommission <= 0) return;

        // إيداع في محفظة الموظف
        // حوّلها عبر خدمة المحفظة: تُسجّل الإيداع للمحصّل
        // والخصم/الدَّين على البائع في نفس العملية
        app(\App\Services\EmployeeWalletService::class)->transferCollectorCommission(
            $collectorCommission,
            $sale->user_id,         // البائع (صاحب السيل)
            $employeeId,            // المُحصّل
            "col:{$collection->id}",// مرجع موحّد
            "عمولة مُحصّل لطريقة #{$method}"
        );

    }
}
