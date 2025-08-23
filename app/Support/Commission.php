<?php

namespace App\Support;

use App\Models\EmployeeMonthlyTarget;
use App\Models\User;

class Commission
{
    public static function monthTargetFor(User $user, int $year, int $month): float
    {
        $rec = EmployeeMonthlyTarget::where('user_id', $user->id)
            ->where('agency_id', $user->agency_id)
            ->where('year', $year)->where('month', $month)
            ->value('main_target');

        return (float)($rec ?? $user->main_target ?? 0);
    }

    public static function monthOverrideRateFor(User $user, int $year, int $month): ?float
    {
        return EmployeeMonthlyTarget::where('user_id', $user->id)
            ->where('agency_id', $user->agency_id)
            ->where('year', $year)->where('month', $month)
            ->value('override_rate');
    }
}
