<?php
namespace App\Livewire\Agency\Reports;

use App\Models\Provider;
use App\Models\Sale;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class ProviderAccountDetails extends Component
{
    public $providerId;
    public $provider;

    // اختيار أعمدة التكلفة تلقائياً
    private function buy($s){ return (float)($s->provider_cost ?? $s->usd_buy ?? $s->buy_price ?? 0); }
    private function refundAmt($s){ return (float)($s->provider_cost_refunded ?? $s->refund_buy ?? 0); }
    private function cancelAmt($s){ return (float)($s->provider_cost_canceled ?? $s->cancel_buy ?? 0); }
    private function paidToProvider($s){ return (float)($s->provider_paid_amount ?? $s->paid_to_provider ?? 0); }

    private function statusAr(string $st): string {
        $s = mb_strtolower(trim($st));
        if (str_contains($s,'refund') && str_contains($s,'partial')) return 'استرداد جزئي';
        if (str_contains($s,'refund')) return 'استرداد';
        if ($s==='void' || str_contains($s,'cancel')) return 'إلغاء';
        if ($s==='issued' || str_contains($s,'reissued')) return 'تم الإصدار';
        if ($s==='pending' || str_contains($s,'submit')) return 'قيد التقديم';
        return $st ?: '-';
    }
    private function d($dt){ try{return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i:s');}catch(\Throwable){return (string)$dt;} }

    // يبني صفوف كشف حساب المزوّد: شراء=مدين، استرداد/إلغاء/مدفوع=دائن
    private function buildRows(): array
{
    $sales = Sale::with('service')
        ->where('agency_id', Auth::user()->agency_id)
        ->where('provider_id', $this->provider->id)
        ->orderBy('created_at')
        ->get();

    $rows = [];

    foreach ($sales->groupBy(fn($s)=>$s->sale_group_id ?? $s->id) as $group) {
        $grpTs = $this->d($group->min('created_at'));

        foreach ($group as $s) {
            $service = (string)($s->service->label ?? '-');
            $ref     = (string)($s->reference ?? "#{$s->id}");
            $stRaw   = mb_strtolower((string)$s->status);
            $st      = $this->statusAr($s->status ?? '');

            // مبالغ الشراء
            $buy = $this->buy($s);

            // كشف نوع الحركة من الحالة أو الإشارة السالبة
            $isRefund = str_contains($stRaw,'refund') || str_contains($stRaw,'استرد') || ($buy < 0);
            $isCancel = str_contains($stRaw,'void')   || str_contains($stRaw,'cancel') || str_contains($stRaw,'إلغاء');

            // قيم الاسترداد/الإلغاء إن لم تكن الحقول موجودة
            $refund = $this->refundAmt($s);
            if ($refund == 0 && $isRefund && $buy < 0) $refund = abs($buy);

            $cancel = $this->cancelAmt($s);
            if ($cancel == 0 && $isCancel && $buy < 0) $cancel = abs($buy);

            // شراء (مدين)
            if ($buy > 0 && !$isRefund && !$isCancel) {
                $rows[] = [
                    'date'=>$this->d($s->created_at),
                    'type'=>'شراء',
                    'status'=>$st,
                    'amount'=>$buy,
                    'ref'=>$ref,
                    'desc'=>"شراء {$service}",
                    'drcr'=>'debit', '_grp'=>$grpTs, '_ord'=>1,
                ];
            }

            // استرداد (دائن)
            if ($refund > 0) {
                $rows[] = [
                    'date'=>$this->d($s->created_at),
                    'type'=>'استرداد',
                    'status'=>'استرداد',
                    'amount'=>$refund,
                    'ref'=>$ref,
                    'desc'=>"استرداد {$service}",
                    'drcr'=>'credit', '_grp'=>$grpTs, '_ord'=>2,
                ];
            }

            // إلغاء (دائن)
            if ($cancel > 0) {
                $rows[] = [
                    'date'=>$this->d($s->created_at),
                    'type'=>'إلغاء',
                    'status'=>'إلغاء',
                    'amount'=>$cancel,
                    'ref'=>$ref,
                    'desc'=>"إلغاء {$service}",
                    'drcr'=>'credit', '_grp'=>$grpTs, '_ord'=>3,
                ];
            }

            // لو عندك سداد للمزوّد وتستخدمه:
            $paid = $this->paidToProvider($s);
            if ($paid > 0) {
                $rows[] = [
                    'date'=>$this->d($s->created_at),
                    'type'=>'سداد للمزوّد',
                    'status'=>'سداد',
                    'amount'=>$paid,
                    'ref'=>$ref,
                    'desc'=>"سداد تكلفة {$service} للمزوّد",
                    'drcr'=>'credit', '_grp'=>$grpTs, '_ord'=>4,
                ];
            }
        }
    }

    usort($rows, fn($a,$b)=>[$a['_grp'],$a['date'],$a['_ord']]<=>[$b['_grp'],$b['date'],$b['_ord']]);
    foreach ($rows as &$r) unset($r['_grp'],$r['_ord']); unset($r);

    $totalDebit  = array_sum(array_map(fn($r)=>$r['drcr']==='debit' ? $r['amount'] : 0, $rows));
    $totalCredit = array_sum(array_map(fn($r)=>$r['drcr']==='credit'? $r['amount'] : 0, $rows));
    $balance     = $totalDebit - $totalCredit;

    return [$rows, $totalDebit, $totalCredit, $balance];
}


    public function mount($id)
    {
        $this->providerId = $id;
        $this->provider   = Provider::where('agency_id', Auth::user()->agency_id)->findOrFail($id);
    }

    public function render()
    {
        [$rows,$tot_buy,$tot_credit,$balance] = $this->buildRows();

        return view('livewire.agency.reportsView.provider-account-details',[
            'rows'=>$rows,
            'tot_buy'=>$tot_buy,
            'tot_credit'=>$tot_credit,
            'balance'=>$balance,
            'currency'=> Auth::user()->agency->currency ?? 'USD',
            'provider'=>$this->provider,
        ]);
    }
}
