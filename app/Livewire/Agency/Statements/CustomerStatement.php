<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Models\Collection;
use App\Models\WalletTransaction;
use Illuminate\Support\Str;

#[Layout('layouts.agency')]
class CustomerStatement extends Component
{
    public Customer $customer;

    public string $beneficiary = '';
    public string $fromDate = '';
    public string $toDate   = '';
    public array  $selectedRows = [];

    /** @var array<int,array{no:int,date:string,desc:string,status:string,debit:float,credit:float,balance:float}> */
    public array $statement = [];
    public float $sumDebit = 0.0;   // إجمالي عليه
    public float $sumCredit = 0.0;  // إجمالي له
    public float $net = 0.0;        // الصافي = له - عليه

    private function statusArabicLabel(string $st): string
    {
        $s = mb_strtolower(trim($st));
        if (str_contains($s, 'refund') && str_contains($s, 'partial')) return 'استرداد جزئي';
        if (str_contains($s, 'refund') && str_contains($s, 'full'))   return 'استرداد كلي';
        if ($s === 'void' || str_contains($s, 'cancel'))              return 'إلغاء';
        if ($s === 'issued' || str_contains($s, 'reissued'))          return 'تم الإصدار';
        if ($s === 'pending' || str_contains($s, 'submit'))           return 'قيد التقديم';
        return $st ?: '-';
    }

    public function mount(Customer $customer)
    {
        abort_unless($customer->agency_id === Auth::user()->agency_id, 403);
        $this->customer = $customer;
        $this->rebuild();
    }

    private function normalize(string $s): string
    {
        $s = str_replace('ـ','', trim($s));
        $map = ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''];
        return mb_strtolower(strtr($s, $map));
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['beneficiary', 'fromDate', 'toDate'], true)) {
            $this->rebuild();
        }
    }

    private function fmtDate($dt): string
    {
        if (!$dt) return '';
        try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i:s'); }
        catch (\Throwable $e) { return (string)$dt; }
    }

    private function rebuild(): void
    {
        $from = ($this->fromDate ?: '0001-01-01') . ' 00:00:00';
        $to   = ($this->toDate   ?: '9999-12-31') . ' 23:59:59';
        $bn   = $this->normalize($this->beneficiary);

        $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
        $void   = ['void','cancel','canceled','cancelled'];

        $rows = [];
        $seq  = 0;

        // معاملات المحفظة
        $walletAffectsBalance = false;
        $walletTx = WalletTransaction::whereHas('wallet', fn($q)=>$q->where('customer_id', $this->customer->id))
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $walletWithdrawAvail = [];
        foreach ($walletTx as $t) {
            if ($t->type === 'withdraw') {
                $k = $this->minuteKey($t->created_at).'|'.$this->moneyKey($t->amount);
                $walletWithdrawAvail[$k] = ($walletWithdrawAvail[$k] ?? 0) + 1;
            }
        }

        // 1) المشتريات
        $sales = $this->customer->sales()
            ->with(['service','customer'])
            ->when($this->fromDate || $this->toDate, fn($q)=>$q->whereBetween('created_at', [$from, $to]))
            ->orderBy('created_at')
            ->get();

        if ($bn !== '') {
            $sales = $sales->filter(function ($s) use ($bn) {
                $name = $this->normalize((string)($s->beneficiary_name ?? $s->customer->name ?? ''));
                return mb_strpos($name, $bn) !== false;
            });
        }

        foreach ($sales as $sale) {
            $st              = mb_strtolower(trim($sale->status ?? ''));
            $serviceName     = (string)($sale->service->label ?? '-');
            $beneficiaryName = (string)($sale->beneficiary_name ?? $this->customer->name);
            $grpTs           = $this->fmtDate($sale->created_at);

            if (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
                $label = ($st === 'pending' || str_contains($st, 'submit'))
                    ? "قيد التقديم {$serviceName} لـ{$beneficiaryName}"
                    : "شراء {$serviceName} لـ{$beneficiaryName}";
                $rows[] = [
                    'no'=>0,'date'=>$this->fmtDate($sale->created_at),'desc'=>$label,
                    'status'=>$this->statusArabicLabel($st),'debit'=>(float)$sale->usd_sell,'credit'=>0.0,'balance'=>0.0,
                    '_grp'=>$grpTs,'_evt'=>$this->fmtDate($sale->created_at),'_ord'=>1,'_seq'=>++$seq,
                ];
            }

            if (in_array($st, $refund, true) || in_array($st, $void, true)) {
                $label = $this->statusArabicLabel($st);
                $rows[] = [
                    'no'=>0,'date'=>$this->fmtDate($sale->created_at),
                    'desc'=> "{$label} لـ{$serviceName} لـ{$beneficiaryName}",
                    'status'=>$label,'debit'=>0.0,'credit'=>abs((float)$sale->usd_sell),'balance'=>0.0,
                    '_grp'=>$grpTs,'_evt'=>$this->fmtDate($sale->created_at),'_ord'=>2,'_seq'=>++$seq,
                ];
            }

            if ((float)$sale->amount_paid > 0) {
                $statusLabel = ($sale->payment_method === 'kash' && (float)$sale->amount_paid >= (float)$sale->usd_sell)
                    ? 'سداد كلي' : 'سداد جزئي';
                $rows[] = [
                    'no'=>0,'date'=>$this->fmtDate($sale->created_at),
                    'desc'=> "{$statusLabel} {$serviceName} لـ{$beneficiaryName}",
                    'status'=>$statusLabel,'debit'=>0.0,'credit'=>(float)$sale->amount_paid,'balance'=>0.0,
                    '_grp'=>$grpTs,'_evt'=>$this->fmtDate($sale->created_at),'_ord'=>3,'_seq'=>++$seq,
                ];
            }
        }

        // 2) التحصيلات
        $collections = Collection::with(['sale.service','sale.customer'])
            ->whereHas('sale', fn($q)=>$q->where('customer_id', $this->customer->id))
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        if ($bn !== '') {
            $collections = $collections->filter(function ($c) use ($bn) {
                $name = $this->normalize((string)($c->sale->beneficiary_name ?? $c->sale->customer->name ?? ''));
                return mb_strpos($name, $bn) !== false;
            });
        }

        // فهرس مفاتيح التحصيلات (دقيقة + مبلغ) لإخفاء سحب محفظة المطابق
        $collectionKeys = [];
        foreach ($collections as $c) {
            $evtC = $this->fmtDate($c->created_at ?? $c->payment_date);
            $kC   = $this->minuteKey($evtC).'|'.$this->moneyKey($c->amount);
            $collectionKeys[$kC] = ($collectionKeys[$kC] ?? 0) + 1;
        }

        $commissionPairMeta = [];
        foreach ($collections as $col) {
            $serviceName     = (string)($col->sale->service->label ?? '-');
            $beneficiaryName = (string)($col->sale->beneficiary_name ?? $col->sale->customer->name ?? $this->customer->name);
            $evt             = $this->fmtDate($col->created_at ?? $col->payment_date);
            $grpTs           = $this->fmtDate($col->sale->created_at);

            $keyC = $this->minuteKey($evt).'|'.$this->moneyKey($col->amount);

            $isCommission = (bool) (data_get($col, 'is_commission')
                ?? (Str::contains(Str::lower((string) data_get($col, 'source', '')), 'commission'))
                ?? (Str::contains(Str::lower((string) data_get($col, 'notes',  '')), 'commission')));

            if (($walletWithdrawAvail[$keyC] ?? 0) > 0) {
                $commissionPairMeta[$keyC][] = [
                    'service'     => $serviceName,
                    'beneficiary' => $beneficiaryName,
                    'grp'         => $grpTs,
                    'evt'         => $evt,
                    'kind'        => $isCommission ? 'commission' : 'collection',
                ];
                continue; // لا تُنشئ صف التحصيل الآن
            }

            // الحالة العادية
            $rows[] = [
                'no'=>0,'date'=>$evt,
                'desc'=>"سداد من التحصيل {$serviceName} لـ{$beneficiaryName}",
                'status'=>'سداد من التحصيل','debit'=>0.0,'credit'=>(float)$col->amount,'balance'=>0.0,
                '_grp'=>$grpTs,'_evt'=>$evt,'_ord'=>4,'_seq'=>++$seq,
            ];
        }

        // 3) حركات المحفظة
        foreach ($walletTx as $tx) {
            $evt = $this->fmtDate($tx->created_at);
            $key = $this->minuteKey($evt).'|'.$this->moneyKey($tx->amount);

            // اخفاء سحب يطابق تحصيلاً بنفس الدقيقة والمبلغ
            if (($tx->type === 'withdraw') && !empty($collectionKeys[$key])) {
                $collectionKeys[$key]--;
                continue;
            }

            // مرجع العملية
            $refStr = Str::lower((string)($tx->reference ?? ''));
            $isCommissionRef = Str::startsWith($refStr, 'commission:group:');

            // إن وُجد pairing مع Collection
            $meta = null;
            if (isset($commissionPairMeta[$key]) && !empty($commissionPairMeta[$key])) {
                $meta = array_shift($commissionPairMeta[$key]);
                if (empty($commissionPairMeta[$key])) unset($commissionPairMeta[$key]);
            }

            $debit = $credit = 0.0;
            $impact = $walletAffectsBalance; // false افتراضيًا
            $status = 'محفظة';
            $desc   = match($tx->type){
                'deposit' => 'إيداع للمحفظة',
                'withdraw'=> 'سحب من المحفظة',
                'adjust'  => 'تعديل رصيد المحفظة',
                default   => 'عملية محفظة',
            };

            if ($tx->type === 'deposit')      { $credit = (float)$tx->amount; }
            elseif ($tx->type === 'withdraw') { $debit  = (float)$tx->amount; }

            $ord = 5.0;

            // عمولة بالمرجع
            if ($isCommissionRef) {
                if ($tx->type === 'deposit') {
                    $status = 'عمولة';
                    $desc   = "إضافة عمولة عميل له {$this->moneyKey($tx->amount)}";
                    $impact = true;
                    $ord    = 5.00;
                } elseif ($tx->type === 'withdraw') {
                    $status = 'عمولة';
                    $desc   = "خصم عمولة عميل عليه {$this->moneyKey($tx->amount)}";
                    $impact = true;
                    $ord    = 5.05;
                }
            }
            // وإلا إن وُجد meta نطبق منطق الإقران
            elseif ($meta) {
                $kind = $meta['kind'] ?? 'collection';
                if ($tx->type === 'deposit') {
                    if ($kind === 'commission') {
                        $status = 'عمولة';
                        $desc   = "إضافة عمولة عميل له {$this->moneyKey($tx->amount)}";
                    } else {
                        $status = 'تحصيل';
                        $desc   = "سداد من التحصيل {$meta['service']} لـ{$meta['beneficiary']}";
                    }
                    $impact = true;
                    $ord    = 5.00;
                } elseif ($tx->type === 'withdraw') {
                    // إخفاء السحب الآلي المستخدم لتسوية التحصيل/العمولة
                    continue;
                }
            }

            $rows[] = [
                'no'=>0,'date'=>$evt,'desc'=>$desc,'status'=>$status,
                'debit'=>$debit,'credit'=>$credit,'balance'=>0.0,
                '_grp'=>$evt,'_evt'=>$evt,'_ord'=>$ord,'_seq'=>++$seq,'_impact'=>$impact,
            ];
        }

        // الفرز
        usort(
            $rows,
            fn($a,$b)=>[$a['_grp'],$a['_evt'],$a['_ord'],$a['_seq']] <=> [$b['_grp'],$b['_evt'],$b['_ord'],$b['_seq']]
        );

        // الرصيد التراكمي + الإجماليات باحترام _impact
        $bal = 0.0; $i = 1; $sumDebit = 0.0; $sumCredit = 0.0;
        foreach ($rows as &$r) {
            if (($r['_impact'] ?? true) === true) {
                $bal += (($r['debit'] ?? 0.0) - ($r['credit'] ?? 0.0));
                $sumDebit  += ($r['debit']  ?? 0.0);
                $sumCredit += ($r['credit'] ?? 0.0);
            }
            $r['balance'] = $bal;
            $r['no'] = $i++;
            unset($r['_grp'],$r['_evt'],$r['_ord'],$r['_seq']);
        }
        unset($r);

        $this->statement = $rows;
        $this->sumDebit  = round($sumDebit, 2);
        $this->sumCredit = round($sumCredit, 2);
        $this->net       = round($this->sumCredit - $this->sumDebit, 2);
    }

    public function render()
    {
        return view('livewire.agency.statements.customer-statement', [
            'customer'  => $this->customer,
            'statement' => $this->statement,
        ]);
    }

    public function exportPdf(?string $scope = 'all')
    {
        $rows = array_map(function($r){
            if (isset($r['_impact'])) unset($r['_impact']);
            return $r;
        }, $this->statement);

        if ($scope === 'selected') {
            $idx  = array_map('intval', $this->selectedRows);
            $rows = array_values(array_intersect_key($rows, array_flip($idx)));
            if (empty($rows)) {
                session()->flash('message', 'اختر صفوفًا أولاً.');
                session()->flash('type', 'warning');
                return;
            }
        }

        $payload = base64_encode(json_encode([
            'customer_id' => $this->customer->id,
            'filters' => [
                'beneficiary' => $this->beneficiary,
                'from'        => $this->fromDate ?: null,
                'to'          => $this->toDate   ?: null,
            ],
            'rows' => $rows,
        ]));

        return redirect()->route('agency.statements.customer.pdf', [
            'customer' => $this->customer->id,
            'data'     => $payload,
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

    public function toggleSelectAll(): void
    {
        $total = count($this->statement);
        if ($total === 0) return;

        if (count($this->selectedRows) === $total) {
            $this->selectedRows = [];
        } else {
            $this->selectedRows = array_keys($this->statement);
        }
    }

    public function resetFilters(): void
    {
        $this->beneficiary = '';
        $this->fromDate    = '';
        $this->toDate      = '';
        $this->rebuild();
    }

    private function minuteKey($dt): string {
        try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i'); }
        catch (\Throwable $e) { return (string)$dt; }
    }

    private function moneyKey($n): string {
        return number_format((float)$n, 2, '.', '');
    }
}
