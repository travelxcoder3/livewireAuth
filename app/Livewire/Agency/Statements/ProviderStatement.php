<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\ProviderPayment; // غيّر الاسم إن لزم
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

#[Layout('layouts.agency')]
class ProviderStatement extends Component
{
    public Provider $provider;

    public string $fromDate = '';
    public string $toDate   = '';
    public array  $selectedRows = [];

    public array $statement = [];
    public float $sumDebit = 0.0;   // عليه
    public float $sumCredit = 0.0;  // له
    public float $net = 0.0;        // له − عليه

    public function mount(Provider $provider)
    {
        abort_unless($provider->agency_id === Auth::user()->agency_id, 403);
        $this->provider = $provider;
        $this->rebuild();
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['fromDate', 'toDate'], true)) $this->rebuild();
    }

    private function fmt($dt): string
    {
        if(!$dt) return '';
        try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i:s'); }
        catch (\Throwable) { return (string)$dt; }
    }

    public function rebuild(): void
    {
        $from = ($this->fromDate ?: '0001-01-01').' 00:00:00';
        $to   = ($this->toDate   ?: '9999-12-31').' 23:59:59';

        $rows = [];

        // أعمدة محتملة للتكلفة (رتّب حسب عملتك)
        $candidates = ['sar_buy','usd_buy','buy_price','provider_cost'];
        $available  = array_values(array_filter($candidates, fn($c)=> Schema::hasColumn('sales',$c)));
        $selectCols = array_merge($available, ['created_at','id']);

        $sales = Sale::where('agency_id', Auth::user()->agency_id)
            ->where('provider_id', $this->provider->id)
            ->when($this->fromDate || $this->toDate, fn($q)=>$q->whereBetween('created_at',[$from,$to]))
            ->orderBy('created_at')
            ->get($selectCols);

        foreach ($sales as $s) {
            $cost = 0.0;
            foreach ($available as $col) {
                $v = $s->{$col} ?? null;
                if ($v !== null) { $cost = (float)$v; break; }
            }

            $rows[] = [
                'no'      => 0,
                'date'    => \Carbon\Carbon::parse($s->created_at)->format('Y-m-d H:i:s'),
                'desc'    => 'قيمة خدمة من المزوّد',
                'status'  => 'شراء',
                'debit'   => 0.0,
                'credit'  => $cost,   // له = تكلفة المزود
                'balance' => 0.0,
                '_ord'    => 1,
            ];
        }

        usort($rows, fn($a,$b)=>[$a['date'],$a['_ord']] <=> [$b['date'],$b['_ord']]);

        $bal=0.0; $i=1;
        foreach($rows as &$r){
            $bal += (($r['debit']??0)-($r['credit']??0));
            $r['balance']=$bal; $r['no']=$i++;
            unset($r['_ord']);
        } unset($r);

        $this->statement = $rows;
        $this->sumCredit = round(array_sum(array_column($rows,'credit')), 2);

        // مدفوعات المزود (اختياري إن كان الجدول موجود)
        $this->sumDebit = 0.0;
        if (Schema::hasTable('provider_payments') && Schema::hasColumn('provider_payments','amount')) {
            $this->sumDebit = (float) ProviderPayment::where('agency_id', Auth::user()->agency_id)
                ->where('provider_id', $this->provider->id)
                ->when($this->fromDate || $this->toDate, fn($q)=>$q->whereBetween('created_at',[$from,$to]))
                ->sum('amount');
            $this->sumDebit = round($this->sumDebit, 2);
        }

        $this->net = round($this->sumCredit - $this->sumDebit, 2);
        $this->selectedRows = [];
    }

    public function exportPdf(?string $scope='all')
    {
        $rows = $this->statement;
        if ($scope==='selected') {
            $idx = array_map('intval',$this->selectedRows);
            $rows = array_values(array_intersect_key($rows, array_flip($idx)));
            if (empty($rows)) {
                session()->flash('message', 'اختر صفاً واحداً على الأقل.');
                session()->flash('type', 'info');
                return;
            }
        }

        $payload = base64_encode(json_encode([
            'provider_id' => $this->provider->id,
            'filters'     => ['from'=>$this->fromDate ?: null,'to'=>$this->toDate ?: null],
            'rows'        => $rows,
            'totals'      => [
                'count'  => count($rows),
                'debit'  => $this->sumDebit,
                'credit' => $this->sumCredit,
                'net'    => $this->net,
            ],
        ]));

        return redirect()->route('agency.statements.provider.pdf', [
            'provider'=>$this->provider->id,
            'data'=>$payload,
        ]);
    }

    public function exportPdfAuto()
    {
        if (empty($this->selectedRows)) {
            session()->flash('message', 'اختر صفاً واحداً على الأقل.');
            session()->flash('type', 'info');
            return;
        }
        return $this->exportPdf('selected');
    }

    public function toggleSelectAll()
    {
        $t=count($this->statement);
        if($t===0) return;
        $this->selectedRows = count($this->selectedRows)===$t ? [] : array_keys($this->statement);
    }

    public function resetFilters()
    {
        $this->fromDate=''; $this->toDate='';
        $this->rebuild();
    }

    public function render()
    {
        return view('livewire.agency.statements.provider-statement', [
            'provider'  => $this->provider,
            'statement' => $this->statement,
        ]);
    }
}
