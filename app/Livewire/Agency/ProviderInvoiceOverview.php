<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class ProviderInvoiceOverview extends Component
{
    public Provider $provider;

    public $allGroups;
    public $groups;

    public array $selectedGroups = [];
    public string $search = '';   // بحث باسم المستفيد
    public string $fromDate = '';
    public string $toDate   = '';

    public $activeRow = null;

    public function mount(Provider $provider)
    {
        abort_unless($provider->agency_id === Auth::user()->agency_id, 403);
        $this->provider = $provider;

        $this->allGroups = $this->buildGroups($provider);
        $this->applyFilters();
    }

    protected function buildGroups(Provider $provider)
    {
        $sales   = $provider->sales()->with(['collections','service','customer'])->get();
        $grouped = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

        return $grouped->map(function ($sales) {
            $s = $sales->sortByDesc('created_at')->first();

            $refundStatuses = [
                'refund-full','refund_full',
                'refund-partial','refund_partial',
                'refunded','refund'
            ];
            $voidStatuses = ['void','cancel','canceled','cancelled'];

            // إجمالي التكلفة (إصدارات فقط)
            $costTotalTrue = $sales->reject(function ($x) use ($refundStatuses, $voidStatuses) {
                    $st = mb_strtolower(trim($x->status ?? ''));
                    return in_array($st, $refundStatuses, true) || in_array($st, $voidStatuses, true);
                })
                ->sum('usd_buy');

            // إجمالي الاستردادات للمزوّد (كموجب)
            $refundTotal = $sales->filter(function ($x) use ($refundStatuses, $voidStatuses) {
                    $st = mb_strtolower(trim($x->status ?? ''));
                    return in_array($st, $refundStatuses, true) || in_array($st, $voidStatuses, true);
                })
                ->sum(fn($x) => abs($x->usd_buy));

            // صافي المستحق للمزوّد
            $netCost = $costTotalTrue - $refundTotal;

            // لا نملك سجلات دفع للمزوّد هنا، اعتبر المدفوع = 0 (يمكن ربطه لاحقاً)
            $paidToProvider = 0.0;

            return (object)[
                'group_key'        => (string)($s->sale_group_id ?? $s->id),
                'beneficiary_name' => $s->beneficiary_name ?? optional($s->customer)->name ?? '—',
                'sale_date'        => $s->sale_date,
                'service_label'    => $s->service->label ?? '-',

                'cost_total_true'  => (float)$costTotalTrue,  // أصل التكلفة
                'refund_total'     => (float)$refundTotal,    // استردادات
                'net_cost'         => (float)$netCost,        // صافي مستحق للمزوّد

                'paid_to_provider'     => (float)$paidToProvider,
                'remaining_for_provider'=> max(0.0, $netCost - $paidToProvider),

                'scenarios' => $sales->map(function ($sale) {
                    return [
                        'date'        => $sale->sale_date,
                        'usd_buy'     => $sale->usd_buy,
                        'status'      => $sale->status,
                        'note'        => $sale->reference ?? '-',
                    ];
                }),
            ];
        })->values();
    }

    protected function normalize(string $s): string
    {
        $s = str_replace('ـ','', $s);
        $map = ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''];
        return mb_strtolower(strtr(trim($s), $map));
    }

    public function applyFilters(): void
    {
        $term = $this->normalize((string)$this->search);
        $from = $this->fromDate ?: '0001-01-01';
        $to   = $this->toDate   ?: '9999-12-31';

        $filtered = collect($this->allGroups)->filter(function($row) use ($term,$from,$to){
            $okName = $term === '' ? true
                : mb_strpos($this->normalize((string)($row->beneficiary_name ?? '')), $term) !== false;
            $date = (string)($row->sale_date ?? '');
            $okDate = ($date >= $from && $date <= $to);
            return $okName && $okDate;
        })->values();

        $this->groups = $filtered;

        $visibleKeys = $filtered->pluck('group_key')->all();
        $this->selectedGroups = array_values(array_intersect($this->selectedGroups, $visibleKeys));
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['search','fromDate','toDate'], true)) {
            $this->applyFilters();
        }
    }

    public function toggleSelectAll()
    {
        $allVisible = collect($this->groups)->pluck('group_key')->map(fn($v)=>(string)$v)->all();
        if (count($this->selectedGroups) === count($allVisible)) {
            $this->selectedGroups = array_values(array_diff($this->selectedGroups, $allVisible));
        } else {
            $this->selectedGroups = array_values(array_unique(array_merge($this->selectedGroups, $allVisible)));
        }
    }

    public function showDetails($index)
    {
        $this->activeRow = $this->groups[$index] ?? null;
    }

    public function closeModal()
    {
        $this->activeRow = null;
    }

    public function resetFilters(): void
    {
        $this->search   = '';
        $this->fromDate = '';
        $this->toDate   = '';
        $this->applyFilters();
    }

    public function render()
    {
        return view('livewire.agency.provider-invoice-overview', [
            'provider' => $this->provider,
            'groups'   => $this->groups,
        ])->layout('layouts.agency');
    }


      public function exportSelected()
    {
        if (empty($this->selectedGroups)) {
            return;
        }

        // حوّل المفاتيح إلى سلسلة ids=1,2,3
        $ids = implode(',', array_map('strval', $this->selectedGroups));

        // توجيه لمسار التصدير ليتولّى إنشاء وتنزيل الـ PDF
        return redirect()->route(
            'agency.provider-invoices.export',   // غيّر الاسم إذا لزم
            ['provider' => $this->provider->id, 'ids' => $ids]
        );
    }
}
