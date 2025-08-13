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
    public array $selectedRows = [];
    /** @var array<int,array{no:int,date:string,desc:string,status:string,debit:float,credit:float,balance:float}> */
    public array $statement = [];
    private function statusArabicLabel(string $st): string
    {
        $s = mb_strtolower(trim($st));
        if (str_contains($s, 'refund') && str_contains($s, 'partial')) return 'استرداد جزئي';
        if (str_contains($s, 'refund') && (str_contains($s, 'full') || str_contains($s, 'full'))) return 'استرداد كلي';
        if ($s === 'void' || str_contains($s, 'cancel')) return 'إلغاء';
        if ($s === 'issued' || str_contains($s, 'reissued')) return 'تم الإصدار';
        if ($s === 'pending' || str_contains($s, 'submit')) return 'قيد التقديم';
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
        // أعد البناء فقط عند تغيّر الفلاتر
        if (in_array($name, ['beneficiary', 'fromDate', 'toDate'], true)) {
            $this->rebuild();
        }
    }

    private function rebuild(): void
    {
        $from = $this->fromDate ?: '0001-01-01';
        $to   = $this->toDate   ?: '9999-12-31';
        $bn   = $this->normalize($this->beneficiary);
    

        $sales = $this->customer->sales()
            ->with(['collections','service','customer'])
            ->whereBetween('sale_date', [$from, $to])
            ->get();

        if ($bn !== '') {
            $sales = $sales->filter(function($s) use($bn){
                $name = $this->normalize((string)($s->beneficiary_name ?? $s->customer->name ?? ''));
                return mb_strpos($name, $bn) !== false;
            });
        }

        $rows = [];
        $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
        $void   = ['void','cancel','canceled','cancelled'];

        foreach ($sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id) as $group) {
            foreach ($group as $sale) {
                $st = mb_strtolower(trim($sale->status ?? ''));
                $serviceName     = (string)($sale->service->label ?? '-');
                $beneficiaryName = (string)($sale->beneficiary_name ?? $this->customer->name);

                // 3-أ: بيع لأول مرة "تم الإصدار" → "شراء (الخدمة) لـ(المستفيد)"
                if (($st === 'issued' || str_contains($st, 'reissued')) 
                    && !in_array($st, $refund, true) && !in_array($st, $void, true)) {
                    $rows[] = [
                        'date'   => (string)$sale->sale_date,
                        'desc'   => "شراء {$serviceName} لـ{$beneficiaryName}",
                        'status' => $this->statusArabicLabel($st),
                        'debit'  => (float)$sale->usd_sell,
                        'credit' => 0.0,
                    ];
                }
                // 3-ب: بيع "قيد التقديم" → "قيد التقديم (الخدمة) لـ(المستفيد)"
                elseif (($st === 'pending' || str_contains($st, 'submit')) 
                    && !in_array($st, $refund, true) && !in_array($st, $void, true)) {
                    $rows[] = [
                        'date'   => (string)$sale->sale_date,
                        'desc'   => "قيد التقديم {$serviceName} لـ{$beneficiaryName}",
                        'status' => $this->statusArabicLabel($st),
                        'debit'  => (float)$sale->usd_sell,
                        'credit' => 0.0,
                    ];
                }
            // 3-ج: أي بيع آخر نشط غير مُسترد وغير ملغى → نعامله كشراء افتراضي
            elseif (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
                $rows[] = [
                    'date'   => (string)$sale->sale_date,
                    'desc'   => "شراء {$serviceName} لـ{$beneficiaryName}",
                    'status' => $this->statusArabicLabel($st),
                    'debit'  => (float)$sale->usd_sell,
                    'credit' => 0.0,
                ];
            }

            // 3-د: استرداد/إلغاء → "(استرداد جزئي|استرداد كلي|إلغاء) لـ(الخدمة) لـ(المستفيد)"
            if (in_array($st, $refund, true) || in_array($st, $void, true)) {
                $label = $this->statusArabicLabel($st);
                $rows[] = [
                    'date'   => (string)$sale->sale_date,
                    'desc'   => "{$label} لـ{$serviceName} لـ{$beneficiaryName}",
                    'status' => $label,
                    'debit'  => 0.0,
                    'credit' => abs((float)$sale->usd_sell),
                ];
            }

            // 3-هـ: سداد من التحصيلات → "سداد (الخدمة) لـ(المستفيد)"
            foreach ($sale->collections as $col) {
                $rows[] = [
                    'date'   => (string)$col->payment_date,
                    'desc'   => "سداد {$serviceName} لـ{$beneficiaryName}",
                    'status' => 'سداد',
                    'debit'  => 0.0,
                    'credit' => (float)$col->amount,
                ];
            }

            // 3-و: دفعة جزئية مرتبطة بالعملية (إن وُجدت) → احتفظ بها كتوضيح منفصل
            if ((float)$sale->amount_paid > 0) {
                $rows[] = [
                    'date'   => (string)$sale->sale_date,
                    'desc'   => "سداد جزئي {$serviceName} لـ{$beneficiaryName}",
                    'status' => 'سداد جزئي',
                    'debit'  => 0.0,
                    'credit' => (float)$sale->amount_paid,
                ];
            }
        }
        }


        usort($rows, fn($a,$b) => [$a['date'],$a['desc']] <=> [$b['date'],$b['desc']]);

        $bal = 0.0; $i = 1;
        foreach ($rows as &$r) { $bal += ($r['debit'] - $r['credit']); $r['no'] = $i++; $r['balance'] = $bal; }
        unset($r);

        $this->statement    = $rows;
        $this->selectedRows = []; // يمسح التحديد فقط عندما تتغير الفلاتر
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
        // لو 'selected' استخدم الصفوف المختارة فقط
        $rows = $this->statement;
        if ($scope === 'selected') {
            $idx = array_map('intval', $this->selectedRows);
            $rows = array_values(array_intersect_key($rows, array_flip($idx)));
            if (empty($rows)) {
                $this->dispatch('notify', type: 'warning', message: 'اختر صفوفًا أولاً.');
                return;
            }
        }

        // ترميز البيانات مؤقتًا للرابط
        $payload = base64_encode(json_encode([
            'customer_id' => $this->customer->id,
            'filters' => [
                'beneficiary' => $this->beneficiary,
                'from' => $this->fromDate ?: null,
                'to'   => $this->toDate   ?: null,
            ],
            'rows' => $rows, // الصفوف المراد طباعتها
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
        return $this->exportPdf('selected'); // لا نطبع الكل أبداً
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

