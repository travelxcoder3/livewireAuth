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
    
        $sales = $this->customer->sales()
            ->with(['collections','service','customer'])
            ->whereBetween('sale_date', [$from, $to])
            ->orderBy('created_at') // مبدئيًا
            ->get();
    
        if ($bn !== '') {
            $sales = $sales->filter(function($s) use($bn){
                $name = $this->normalize((string)($s->beneficiary_name ?? $s->customer->name ?? ''));
                return mb_strpos($name, $bn) !== false;
            });
        }
    
        $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
        $void   = ['void','cancel','canceled','cancelled'];
    
        $rows = [];
    
        // نجمع الأحداث لكل عملية
        foreach ($sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id) as $groupId => $group) {
            // عنوان العملية (اختياري: سطر فاصل)
            $rows[] = [
                'no'      => 0,
                'date'    => $this->fmtDate(optional($group->first())->created_at),
                'desc'    => "عملية #{$groupId}",
                'status'  => '—',
                'debit'   => 0.0,
                'credit'  => 0.0,
                'balance' => 0.0,
                '_sort'   => $this->fmtDate(optional($group->first())->created_at) . '::0'
            ];
    
            // أحداث العملية
            foreach ($group as $sale) {
                $st = mb_strtolower(trim($sale->status ?? ''));
                $serviceName     = (string)($sale->service->label ?? '-');
                $beneficiaryName = (string)($sale->beneficiary_name ?? $this->customer->name);
    
                // إصدار/قيد التقديم/نشط آخر: تاريخ الإنشاء الفعلي
                if (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
                    $label = ($st === 'pending' || str_contains($st, 'submit'))
                        ? "قيد التقديم {$serviceName} لـ{$beneficiaryName}"
                        : "شراء {$serviceName} لـ{$beneficiaryName}";
                    $rows[] = [
                        'date'    => $this->fmtDate($sale->created_at), // هنا created_at
                        'desc'    => $label,
                        'status'  => $this->statusArabicLabel($st),
                        'debit'   => (float)$sale->usd_sell,
                        'credit'  => 0.0,
                        '_sort'   => $this->fmtDate($sale->created_at) . '::1',
                    ];
                }
    
                // استرداد/إلغاء: بتاريخ إنشائه الفعلي
                if (in_array($st, $refund, true) || in_array($st, $void, true)) {
                    $label = $this->statusArabicLabel($st);
                    $rows[] = [
                        'date'    => $this->fmtDate($sale->created_at),
                        'desc'    => "{$label} لـ{$serviceName} لـ{$beneficiaryName}",
                        'status'  => $label,
                        'debit'   => 0.0,
                        'credit'  => abs((float)$sale->usd_sell),
                        '_sort'   => $this->fmtDate($sale->created_at) . '::2',
                    ];
                }
    
                // دفعة جزئية مرتبطة بالسجل
                if ((float)$sale->amount_paid > 0) {
                    $rows[] = [
                        'date'    => $this->fmtDate($sale->created_at),
                        'desc'    => "سداد جزئي {$serviceName} لـ{$beneficiaryName}",
                        'status'  => 'سداد جزئي',
                        'debit'   => 0.0,
                        'credit'  => (float)$sale->amount_paid,
                        '_sort'   => $this->fmtDate($sale->created_at) . '::3',
                    ];
                }
    
                // التحصيلات: بتاريخ إنشاء التحصيل الفعلي
                foreach ($sale->collections as $col) {
                    $rows[] = [
                        'date'    => $this->fmtDate($col->created_at ?? $col->payment_date),
                        'desc'    => "سداد {$serviceName} لـ{$beneficiaryName}",
                        'status'  => 'سداد',
                        'debit'   => 0.0,
                        'credit'  => (float)$col->amount,
                        '_sort'   => $this->fmtDate($col->created_at ?? $col->payment_date) . '::4',
                    ];
                }
            }
        }
    
        // فرز زمني داخل كل العملية ثم عام
        usort($rows, function ($a, $b) {
            return strcmp($a['_sort'] ?? $a['date'], $b['_sort'] ?? $b['date'])
                ?: strcmp($a['desc'], $b['desc']);
        });
    
        // أرقام وبالنس تراكمي
        $bal = 0.0; $i = 1;
        foreach ($rows as &$r) {
            if (($r['debit'] ?? 0) !== 0.0 || ($r['credit'] ?? 0) !== 0.0) {
                $bal += (($r['debit'] ?? 0) - ($r['credit'] ?? 0));
                $r['balance'] = $bal;
                $r['no'] = $i++;
            } else {
                // سطر عنوان العملية لا يؤثر على الرصيد
                $r['balance'] = $bal;
                $r['no'] = null;
            }
            unset($r['_sort']);
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

