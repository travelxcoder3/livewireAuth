<?php

namespace App\Services;

use App\Models\Sale;

class SalesKpi
{
    /**
     * المؤجّل (غير المُحصَّل) ضمن نطاق تاريخي محدد
     * بنفس منطق صفحة المبيعات: تجميع حسب sale_group_id،
     * تجاهل Refund-Full و Void، وعدم احتساب قيم سالبة كمؤجل.
     */
    public static function deferredForRange(
        int $agencyId,
        string $startDate,
        string $endDate,
        ?int $userId = null,
        bool $isAdmin = false
    ): float {
        $q = Sale::query()
            ->where('agency_id', $agencyId)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->whereNotIn('status', ['Void']) // نهمل الملغاة
            ->withSum('collections', 'amount');

        if (!$isAdmin && $userId) {
            $q->where('user_id', $userId);
        }

        $groups = $q->get()->groupBy('sale_group_id');

        $deferred = 0.0;

        foreach ($groups as $group) {
            // إذا المجموعة فيها استرداد كلي → المؤجل = 0
            if ($group->contains(fn ($s) => $s->status === 'Refund-Full')) {
                continue;
            }

            // إجمالي البيع للمجموعة (قد يكون مخفّض بسبب سطور سالب)
            $sell = (float) $group->sum('usd_sell');

            // المدفوع لحظة البيع (نحسبه فقط لطريقتي part/all)
            $atSalePaid = (float) $group->sum(function ($s) {
                return in_array($s->payment_method, ['part', 'all'])
                    ? (float) $s->amount_paid
                    : 0.0;
            });

            // مجموع التحصيلات
            $collected = (float) $group->sum(fn ($s) => (float) ($s->collections_sum_amount ?? 0));

            // استرداد جزئي للعميل (لو تسجّل كسطر بيع سالب)
            $refundedPartial = (float) $group
                ->where('status', 'Refund-Partial')
                ->sum(fn ($s) => $s->usd_sell < 0 ? abs((float) $s->usd_sell) : 0.0);

            // المؤجّل = بيع - (مدفوع + مُحصّل) - مسترد للعميل
            $groupDeferred = $sell - ($atSalePaid + $collected) - $refundedPartial;

            if ($groupDeferred > 0) {
                $deferred += $groupDeferred;
            }
        }

        return round($deferred, 2);
    }
}
