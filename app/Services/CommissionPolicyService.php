<?php

namespace App\Services;

use App\Models\{CommissionProfile, CommissionEmployeeRateOverride};

class CommissionPolicyService
{
    public function employeeRateFor(int $userId, int $agencyId): float {
        $profile = CommissionProfile::where('agency_id',$agencyId)->where('is_active',true)->first();
        if (!$profile) return 0;
        $ovr = CommissionEmployeeRateOverride::where('profile_id',$profile->id)
                ->where('user_id',$userId)->value('rate');
        return $ovr !== null ? (float)$ovr : (float)($profile->employee_rate ?? 0);
    }

    public function debtPolicy(int $agencyId): array {
        $profile = CommissionProfile::where('agency_id',$agencyId)->where('is_active',true)->first();
        return [
            'days' => (int)($profile->days_to_debt ?? 0),
            'behavior' => $profile->debt_behavior ?? 'deduct_commission_until_paid',
        ];
    }
}
