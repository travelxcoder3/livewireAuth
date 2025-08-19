<?php

namespace App\Http\Controllers\Agency\Reports;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Str;

class ProviderAccountPdfController extends Controller
{
    private function buy($s){ return (float)($s->provider_cost ?? $s->usd_buy ?? $s->buy_price ?? 0); }
    private function refundAmt($s){ return (float)($s->provider_cost_refunded ?? $s->refund_buy ?? 0); }
    private function cancelAmt($s){ return (float)($s->provider_cost_canceled ?? $s->cancel_buy ?? 0); }

    private function statusAr(?string $st): string {
        $s = mb_strtolower(trim((string)$st));
        if (str_contains($s,'refund') && str_contains($s,'partial')) return 'استرداد جزئي';
        if (str_contains($s,'refund')) return 'استرداد';
        if ($s==='void' || str_contains($s,'cancel')) return 'إلغاء';
        if ($s==='issued' || str_contains($s,'reissued')) return 'تم الإصدار';
        if ($s==='pending' || str_contains($s,'submit')) return 'قيد التقديم';
        return $st ?: '-';
    }

    public function __invoke(Request $request, $id)
    {
        $agency   = Auth::user()->agency;
        $agencyId = $agency->id;
        $provider = Provider::where('agency_id',$agencyId)->findOrFail($id);

        $sales = Sale::with('service')
            ->where('agency_id',$agencyId)
            ->where('provider_id',$provider->id)
            ->orderBy('created_at')
            ->get();

        $rows = [];
        $total_buy = $total_refund = $total_cancel = $total_penalty = 0;

        foreach ($sales->groupBy(fn($s)=>$s->sale_group_id ?? $s->id) as $group) {
            foreach ($group as $s) {
                $service = (string)($s->service->label ?? '-');
                $ref     = (string)($s->reference ?? "#{$s->id}");
                $stRaw   = mb_strtolower((string)$s->status);
                $st      = $this->statusAr($s->status ?? '');

                $buy    = $this->buy($s);
                $refund = $this->refundAmt($s);
                $cancel = $this->cancelAmt($s);

                $isRefund = str_contains($stRaw,'refund');
                $isCancel = str_contains($stRaw,'void') || str_contains($stRaw,'cancel');

                // شراء
                if ($buy > 0 && !$isRefund && !$isCancel && $refund==0 && $cancel==0) {
                    $rows[] = [
                        'date'=>optional($s->created_at)->format('Y-m-d'),
                        'type'=>'شراء','status'=>$st,'amount'=>$buy,'ref'=>$ref,
                        'desc'=>"شراء {$service}",
                    ];
                    $total_buy += $buy;
                }

                // استرداد
                if ($refund == 0 && $isRefund && $buy < 0) $refund = abs($buy); // fallback
                if ($refund > 0) {
                    $rows[] = [
                        'date'=>optional($s->created_at)->format('Y-m-d'),
                        'type'=>'استرداد','status'=>'استرداد','amount'=>$refund,'ref'=>$ref,
                        'desc'=>"استرداد {$service}",
                    ];
                    $total_refund += $refund;
                    if ($buy > 0 && $refund < $buy) $total_penalty += ($buy - $refund); // غرامة
                }

                // إلغاء
                if ($cancel == 0 && $isCancel && $buy < 0) $cancel = abs($buy); // fallback
                if ($cancel > 0) {
                    $rows[] = [
                        'date'=>optional($s->created_at)->format('Y-m-d'),
                        'type'=>'إلغاء','status'=>'إلغاء','amount'=>$cancel,'ref'=>$ref,
                        'desc'=>"إلغاء {$service}",
                    ];
                    $total_cancel += $cancel;
                    if ($buy > 0 && $cancel < $buy) $total_penalty += ($buy - $cancel); // غرامة
                }
            }
        }

        $net_cost = $total_buy - ($total_refund + $total_cancel);
        $balance  = $net_cost;

        // ⚠️ اسم ملف العرض يجب أن يطابق مسارك الفعلي
        $html = view('pdf.provider-account-details-pdf', [
            'provider'    => $provider,
            'agency'      => $agency,
            'rows'        => $rows,
            'tot_buy'     => $total_buy,
            'tot_credit'  => $total_refund + $total_cancel, // (استرداد + إلغاء)
            'tot_penalty' => $total_penalty,
            'balance'     => $balance,
            'currency'    => $agency->currency ?? 'USD',
        ])->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(14, 10, 14, 10)
            ->showBackground()
            ->scale(1)
            ->pdf();

        $filename = 'provider-account-'.Str::slug($provider->name).'-'.$provider->id.'.pdf';

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
