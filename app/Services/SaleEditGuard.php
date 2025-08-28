<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\ApprovalRequest;

class SaleEditGuard
{
    public static function minutes(Sale $sale): int
    {
        return max(0, (int) ($sale->agency?->editSaleWindowMinutes() ?? 180));
    }

    public static function withinWindow(Sale $sale): bool
    {
        $mins = self::minutes($sale);
        if ($mins === 0) return false;
        return now()->diffInMinutes($sale->created_at) < $mins;
    }

    public static function hasApproved(Sale $sale): bool
    {
        return ApprovalRequest::where('model_type', Sale::class)
            ->where('model_id', $sale->id)
            ->where('status', 'approved')
            ->exists();
    }

    public static function hasPending(Sale $sale): bool
    {
        return ApprovalRequest::where('model_type', Sale::class)
            ->where('model_id', $sale->id)
            ->where('status', 'pending')
            ->exists();
    }

    /** مسموح التعديل الآن؟ */
    public static function canEditNow(Sale $sale): bool
    {
        return self::withinWindow($sale) || self::hasApproved($sale);
    }
}