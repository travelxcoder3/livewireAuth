<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Provider;
use App\Models\Sale;
use Carbon\Carbon;
use App\Models\DynamicListItem;

class ProviderLedger extends Component
{
    use WithPagination;

    public string $providerName = '';
    public ?string $fromDate = null;
    public ?string $toDate   = null;

    public function resetFilters(): void
    {
        $this->reset(['providerName','fromDate','toDate']);
        $this->resetPage();
    }

    private function buy($s){ return $s->provider_cost ?? $s->usd_buy ?? $s->buy_price ?? 0; }
    private function refund($s){ return $s->provider_cost_refunded ?? $s->refund_buy ?? $s->refunded_buy ?? 0; }
    private function cancel($s){ return $s->provider_cost_canceled ?? $s->cancel_buy ?? $s->canceled_buy ?? 0; }
    private function isRefund($s){
        $st = strtolower((string)($s->status ?? ''));
        return str_contains($st,'refund') || (float)$this->buy($s) < 0;
    }
    private function isCancel($s){
        $st = strtolower((string)($s->status ?? ''));
        return str_contains($st,'cancel') || str_contains($st,'void');
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        // أسماء الخدمات من "قائمة الخدمات" (عام + خاص بالوكالة)
        $serviceLabels = DynamicListItem::whereHas('list', function ($q) {
                $q->where('name', 'قائمة الخدمات');
            })
            ->where(function ($q) {
                $q->where('created_by_agency', auth()->user()->agency_id)
                ->orWhereNull('created_by_agency');
            })
            ->pluck('label', 'id')
            ->toArray();

        // مبيعات ضمن الفلاتر
        $sales = Sale::where('agency_id', $agencyId)
            ->when(trim($this->providerName) !== '', fn($q)=>
                $q->whereHas('provider', fn($qq)=>$qq->where('name','like','%'.trim($this->providerName).'%'))
            )
            ->when($this->fromDate, fn($q)=>$q->whereDate('sale_date','>=', Carbon::parse($this->fromDate)->startOfDay()))
            ->when($this->toDate,   fn($q)=>$q->whereDate('sale_date','<=', Carbon::parse($this->toDate)->endOfDay()))
            ->get();

        // خريطة أسماء المزودين لتفادي N+1
        $providerIds = $sales->pluck('provider_id')->unique()->filter()->values();
        $providersMap = Provider::whereIn('id', $providerIds)->pluck('name','id');

        // تجميع مزوّد + خدمة
        $groups = $sales->groupBy(fn($s)=> ($s->provider_id ?? 0).'|'.($s->service_type_id ?? 0));

        $rowsAll = $groups->map(function($group) use ($providersMap, $serviceLabels) {
            $first  = $group->first();
            $pName  = $providersMap[$first->provider_id] ?? '-';
            $sLabel = $serviceLabels[$first->service_type_id] ?? 'غير محدد';
            $count  = $group->count();

            $buy = $group->sum(function($s){ $b=(float)($this->buy($s)); return $b>0?$b:0; });
            $refund = $group->sum(function($s){
                $r=(float)($this->refund($s));
                if ($r==0 && $this->isRefund($s)) $r = abs((float)$this->buy($s));
                return $r;
            });
            $cancel = $group->sum(function($s){
                $c=(float)($this->cancel($s));
                if ($c==0 && $this->isCancel($s) && (float)$this->buy($s) < 0) $c = abs((float)$this->buy($s));
                return $c;
            });

            $net = $buy - $refund - $cancel;

            return (object)[
                'provider_name' => $pName,
                'service_label' => $sLabel,
                'tx_count'      => $count,
                'for_provider'  => max(0, $net),     // له
                'for_agency'    => max(0, -$net),    // عليه
                'net'           => $net,             // الرصيد
            ];
        })->values();

        // ترقيم صفحات يدوي
        $perPage = 10;
        $page    = (int) request()->query('page', 1);
        $slice   = $rowsAll->slice(($page-1)*$perPage, $perPage)->values();

        $rows = new LengthAwarePaginator($slice, $rowsAll->count(), $perPage, $page, [
            'path'=>request()->url(), 'query'=>request()->query()
        ]);

        $totalForProviders = $rowsAll->sum('for_provider');
        $totalForAgency    = $rowsAll->sum('for_agency');

        return view('livewire.agency.reportsView.provider-ledger',
            compact('rows','totalForProviders','totalForAgency')
        )->layout('layouts.agency');
    }

}
