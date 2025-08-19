<?php
namespace App\Livewire\Agency\Reports;

use App\Models\Provider;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Carbon\Carbon;

#[Layout('layouts.agency')]
class ProviderAccounts extends Component
{
    use WithPagination;

    public $providerName = '';
    public $fromDate = '';
    public $toDate = '';

    public function resetFilters()
    {
        $this->fromDate = $this->toDate = null;
        $this->providerName = '';
    }

    private function buy($s){ return $s->provider_cost ?? $s->usd_buy ?? $s->buy_price ?? 0; }
private function refund($s){ return $s->provider_cost_refunded ?? $s->refund_buy ?? $s->refunded_buy ?? 0; }
private function cancel($s){ return $s->provider_cost_canceled ?? $s->cancel_buy ?? $s->canceled_buy ?? 0; }
private function isRefund($s){
    $st = mb_strtolower((string)$s->status);
    return str_contains($st,'refund') || $this->buy($s) < 0;
}
private function isCancel($s){
    $st = mb_strtolower((string)$s->status);
    return str_contains($st,'cancel') || str_contains($st,'void');
}

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $providersQuery = Provider::where('agency_id', $agencyId);
        if (trim($this->providerName) !== '') {
            $providersQuery->where('name','like','%'.trim($this->providerName).'%');
        }
        $providers = $providersQuery->get();
        $providerIds = $providers->pluck('id');

        $sales = Sale::where('agency_id', $agencyId)
            ->whereIn('provider_id', $providerIds)
            ->when($this->fromDate, fn($q)=>$q->whereDate('sale_date','>=', Carbon::parse($this->fromDate)->startOfDay()))
            ->when($this->toDate,   fn($q)=>$q->whereDate('sale_date','<=', Carbon::parse($this->toDate)->endOfDay()))
            ->get();

       $rows = $providers->map(function($p) use ($sales){
    $ps = $sales->where('provider_id', $p->id);

    // اجمالي الشراء يحسب القيم الموجبة فقط
    $total_buy = $ps->sum(function($s){
        $b = (float)$this->buy($s);
        return $b > 0 ? $b : 0;
    });

    // الاسترداد: من الحقول، وإلا من الحالة/الإشارة السالبة
    $total_refund = $ps->sum(function($s){
        $r = (float)$this->refund($s);
        if ($r == 0 && $this->isRefund($s)) {
            $r = abs((float)$this->buy($s)); // التقط -usd_buy
        }
        return $r;
    });

    // الإلغاء بنفس الفكرة
    $total_cancel = $ps->sum(function($s){
        $c = (float)$this->cancel($s);
        if ($c == 0 && $this->isCancel($s) && (float)$this->buy($s) < 0) {
            $c = abs((float)$this->buy($s));
        }
        return $c;
    });

    $net_cost = $total_buy - $total_refund - $total_cancel;
    $balance  = $net_cost;

    return [
        'id'    => $p->id,
        'name'  => $p->name,
        'buy'   => $total_buy,
        'refund'=> $total_refund,
        'cancel'=> $total_cancel,
        'net'   => $net_cost,
        'for_provider' => max(0, $balance),
        'for_agency'   => max(0, -$balance),
        'last_sale_date'=> optional($ps->sortByDesc('id')->first())->sale_date,
    ];
});

        $totalForProviders = $rows->sum('for_provider');
        $totalForAgency    = $rows->sum('for_agency');

        return view('livewire.agency.reportsView.provider-accounts', [
            'providers' => $rows,
            'totalForProviders' => $totalForProviders,
            'totalForAgency' => $totalForAgency,
        ]);
    }
}
