<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

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

    private function statusArabicLabel(string $st): string
    {
        $s = mb_strtolower(trim($st));
        if (str_contains($s, 'refund') && str_contains($s, 'partial')) return 'استرداد جزئي';
        if (str_contains($s, 'refund') && (str_contains($s, 'full')))     return 'استرداد كلي';
        if ($s === 'void' || str_contains($s, 'cancel'))                  return 'إلغاء';
        if ($s === 'issued' || str_contains($s, 'reissued'))              return 'تم الإصدار';
        if ($s === 'pending' || str_contains($s, 'submit'))               return 'قيد التقديم';
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
        $from = $this->fromDate ?: '0001-01-01';
        $to   = $this->toDate   ?: '9999-12-31';
        $bn   = $this->normalize($this->beneficiary);

        // التصفية والمدى الزمني بالـ created_at
        $sales = $this->customer->sales()
            ->with(['collections', 'service', 'customer'])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        if ($bn !== '') {
            $sales = $sales->filter(function ($s) use ($bn) {
                $name = $this->normalize((string)($s->beneficiary_name ?? $s->customer->name ?? ''));
                return mb_strpos($name, $bn) !== false;
            });
        }

        $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
        $void   = ['void','cancel','canceled','cancelled'];

        $rows = [];

        // تجميع حسب العملية ثم بناء أحداثها بدون سطر عنوان
        foreach ($sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id) as $group) {

            $grpTs = $this->fmtDate($group->min('created_at')); // بداية العملية

            foreach ($group as $sale) {
                $st              = mb_strtolower(trim($sale->status ?? ''));
                $serviceName     = (string)($sale->service->label ?? '-');
                $beneficiaryName = (string)($sale->beneficiary_name ?? $this->customer->name);

                // شراء/قيد التقديم/نشط آخر
                if (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
                    $label = ($st === 'pending' || str_contains($st, 'submit'))
                        ? "قيد التقديم {$serviceName} لـ{$beneficiaryName}"
                        : "شراء {$serviceName} لـ{$beneficiaryName}";
                    $rows[] = [
                        'no'      => 0,
                        'date'    => $this->fmtDate($sale->created_at),
                        'desc'    => $label,
                        'status'  => $this->statusArabicLabel($st),
                        'debit'   => (float)$sale->usd_sell,
                        'credit'  => 0.0,
                        'balance' => 0.0,
                        '_grp'    => $grpTs,
                        '_evt'    => $this->fmtDate($sale->created_at),
                        '_ord'    => 1,
                    ];
                }

                // استرداد/إلغاء
                if (in_array($st, $refund, true) || in_array($st, $void, true)) {
                    $label = $this->statusArabicLabel($st);
                    $rows[] = [
                        'no'      => 0,
                        'date'    => $this->fmtDate($sale->created_at),
                        'desc'    => "{$label} لـ{$serviceName} لـ{$beneficiaryName}",
                        'status'  => $label,
                        'debit'   => 0.0,
                        'credit'  => abs((float)$sale->usd_sell),
                        'balance' => 0.0,
                        '_grp'    => $grpTs,
                        '_evt'    => $this->fmtDate($sale->created_at),
                        '_ord'    => 2,
                    ];
                }

               
// دفعات مباشرة من العملية نفسها
if ((float)$sale->amount_paid > 0) {
    if ($sale->payment_method === 'kash' && (float)$sale->amount_paid >= (float)$sale->usd_sell) {
        $statusLabel = 'سداد كلي';
    } else {
        $statusLabel = 'سداد جزئي';
    }
    $rows[] = [
        'no'      => 0,
        'date'    => $this->fmtDate($sale->created_at),
        'desc'    => "{$statusLabel} {$serviceName} لـ{$beneficiaryName}",
        'status'  => $statusLabel,
        'debit'   => 0.0,
        'credit'  => (float)$sale->amount_paid,
        'balance' => 0.0,
        '_grp'    => $grpTs,
        '_evt'    => $this->fmtDate($sale->created_at),
        '_ord'    => 3,
    ];
}

// التحصيلات
foreach ($sale->collections as $col) {
    $evt = $this->fmtDate($col->created_at ?? $col->payment_date);
    $rows[] = [
        'no'      => 0,
        'date'    => $evt,
        'desc'    => "سداد من التحصيل {$serviceName} لـ{$beneficiaryName}",
        'status'  => 'سداد من التحصيل',
        'debit'   => 0.0,
        'credit'  => (float)$col->amount,
        'balance' => 0.0,
        '_grp'    => $grpTs,
        '_evt'    => $evt,
        '_ord'    => 4,
    ];
}

            }
        }

        // فرز: بداية العملية ثم توقيت الحدث ثم نوعه
        usort($rows, fn($a, $b) => [$a['_grp'],$a['_evt'],$a['_ord']] <=> [$b['_grp'],$b['_evt'],$b['_ord']]);

        // أرقام ورصيد تراكمي + تنظيف مفاتيح الفرز
        $bal = 0.0; $i = 1;
        foreach ($rows as &$r) {
            $bal += (($r['debit'] ?? 0.0) - ($r['credit'] ?? 0.0));
            $r['balance'] = $bal;
            $r['no']      = $i++;
            unset($r['_grp'], $r['_evt'], $r['_ord']);
        }
        unset($r);

        $this->statement    = $rows;
        $this->selectedRows = [];
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
        $rows = $this->statement;

        if ($scope === 'selected') {
            $idx  = array_map('intval', $this->selectedRows);
            $rows = array_values(array_intersect_key($rows, array_flip($idx)));
            if (empty($rows)) {
                $this->dispatch('notify', type: 'warning', message: 'اختر صفوفًا أولاً.');
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
            $this->dispatch('notify', type: 'warning', message: 'اختر صفاً واحداً على الأقل.');
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
            $this->selectedRows = array_keys($this->statement); // 0..n-1
        }
    }
}
