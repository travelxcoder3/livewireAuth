<?php

namespace App\Livewire\Agency;

use App\Models\Customer;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class CustomerInvoiceOverview extends Component
{
    public Customer $customer;

    /** جميع العناصر غير المفلترة (أصل البيانات) */
    public $allCollections;

    /** العناصر المعروضة بعد الفلترة */
    public $collections;

    public $activeSale = null;

    // ✅ مفاتيح المجموعات المختارة (sale_group_id أو id)
    public array $selectedGroups = [];

    // ✅ فلاتر
    public string $search = '';     // بحث باسم المستفيد
    public string $fromDate = '';   // yyyy-mm-dd
    public string $toDate   = '';   // yyyy-mm-dd

    public ?string $toastMessage = null;
    public ?string $toastType    = null;

    private function clearToast(): void
    {
        $this->toastMessage = null;
        $this->toastType = null;
    }

    public function mount(Customer $customer)
    {
        abort_unless($customer->agency_id === Auth::user()->agency_id, 403);

        $this->customer = $customer;

        // نبني المصدر مرة واحدة
        $this->allCollections = $this->buildCollections($customer);

        // أول عرض بدون فلترة
        $this->applyFilters();
    }

    /** يبني مصفوفة التجميعات من السيلز */
    protected function buildCollections(Customer $customer)
    {
        $sales   = $customer->sales()->with(['collections', 'service'])->get();
        $grouped = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

    return $grouped->map(function ($sales) {
        $s = $sales->sortByDesc('created_at')->first();

$refundStatuses = [
    'refund-full','refund_full',
    'refund-partial','refund_partial',
    'refunded','refund'
];
$voidStatuses   = ['void','cancel','canceled','cancelled'];

// أصل الفواتير = كل الإصدارات الفعلية فقط
$invoiceTotalTrue = $sales->reject(function ($x) use ($refundStatuses, $voidStatuses) {
        $st = mb_strtolower(trim($x->status ?? ''));
        return in_array($st, $refundStatuses, true) || in_array($st, $voidStatuses, true);
    })
    ->sum('usd_sell');

// إجمالي الاستردادات (يشمل الإلغاء) كموجب
$refundTotal = $sales->filter(function ($x) use ($refundStatuses, $voidStatuses) {
        $st = mb_strtolower(trim($x->status ?? ''));
        return in_array($st, $refundStatuses, true) || in_array($st, $voidStatuses, true);
    })
    ->sum(fn($x) => abs($x->usd_sell));


        // الصافي بعد الاستردادات
        $netTotal = $invoiceTotalTrue - $refundTotal;

        // التحصيلات + amount_paid
        $collected = $sales->sum(fn($x) => $x->collections->sum('amount'));
        $paid      = $sales->sum('amount_paid');
        $totalCollected = $collected + $paid;

        // أرصدة
        $remainingForCustomer = max(0, $netTotal - $totalCollected);
        $remainingForCompany  = max(0, $totalCollected - $netTotal);

        return (object) [
            'group_key'        => (string) ($s->sale_group_id ?? $s->id),
            'beneficiary_name' => $s->beneficiary_name ?? optional($s->customer)->name ?? '—',
            'sale_date'        => $s->sale_date,
            'service_label'    => $s->service->label ?? '-',

            // الحقول الجديدة التي سنعرضها
            'invoice_total_true'   => $invoiceTotalTrue,   // أصل الفاتورة
            'refund_total'         => $refundTotal,        // اجمالي الاستردادات
            'net_total'            => $netTotal,           // الصافي = الأصل - الاسترداد
            'total_collected'      => $totalCollected,     // إجمالي المحصّل (تحصيلات + amount_paid)

            'remaining_for_customer' => $remainingForCustomer, // متبقي على العميل
            'remaining_for_company'  => $remainingForCompany,  // للشركة للعميل

            // توافق خلفي إن لزم
            'amount_paid'  => $paid,
            'collected'    => $collected,
            'total_paid'   => $totalCollected,
            'usd_sell'     => $netTotal,    // كان يُستخدم سابقاً كصافي
            'remaining'    => $remainingForCustomer,

            'note' => $s->note,
            'scenarios' => $sales->map(function ($sale) {
                return [
                    'date'        => $sale->sale_date,
                    'usd_sell'    => $sale->usd_sell,
                    'amount_paid' => $sale->amount_paid,
                    'status'      => $sale->status,
                    'note'        => $sale->reference ?? '-',
                ];
            }),
'collections' => $sales->flatMap->collections->map(function ($col) {
    $note = $this->sanitizeCollectionNote($col->note);
    return [
        'amount'       => $col->amount,
        'payment_date' => $col->payment_date,
        'note'         => $note,
    ];
}),

        ];
    })->values();

    }

    /** يطبّق الفلاتر على allCollections ويحدّث collections */
    public function applyFilters(): void
    {
        // طبّع نص البحث (يحذف الكشيدة ويوحد الألف/الياء/التاء المربوطة والهمزات ويحول لصغير)
        $term = $this->normalize((string) $this->search);

        $from = $this->fromDate ?: '0001-01-01';
        $to   = $this->toDate   ?: '9999-12-31';

        $filtered = collect($this->allCollections)
            ->filter(function ($row) use ($term, $from, $to) {
                // فلترة الاسم بعد التطبيع للطرفين
                $okName = true;
                if ($term !== '') {
                    $nameNorm = $this->normalize((string)($row->beneficiary_name ?? ''));
                    $okName   = mb_strpos($nameNorm, $term) !== false;
                }

                // فلترة التاريخ (sale_date بين from/to)
                $date   = (string) ($row->sale_date ?? '');
                $okDate = ($date >= $from && $date <= $to);

                return $okName && $okDate;
            })
            ->values();

        $this->collections = $filtered;

        // حافظ على التحديد للعناصر الظاهرة فقط
        $visibleKeys = $filtered->pluck('group_key')->all();
        $this->selectedGroups = array_values(array_intersect($this->selectedGroups, $visibleKeys));
        $this->clearToast(); // ← لا تعيد إظهار التوست بعد التبديل
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['search', 'fromDate', 'toDate'], true)) {
            $this->applyFilters();
        }
    }


    /** أعيد الحساب عند تغيير أي فلتر */
    public function updatedSearch()  { $this->applyFilters(); }
    public function updatedFromDate(){ $this->applyFilters(); }
    public function updatedToDate()  { $this->applyFilters(); }

    public function toggleSelection($index)
    {
        $key = (string) $this->collections[$index]->group_key;
        if (in_array($key, $this->selectedGroups, true)) {
            $this->selectedGroups = array_values(array_diff($this->selectedGroups, [$key]));
        } else {
            $this->selectedGroups[] = $key;
        }
        $this->clearToast();
    }

    public function showDetails($index)
    {
        $this->clearToast();
        $this->activeSale = $this->collections[$index];
    }

    public function closeModal()
    {
        $this->clearToast();
        $this->activeSale = null;
    }

  
    /** تحديد/إلغاء تحديد الكل (للصفوف الظاهرة فقط) */
    public function toggleSelectAll()
    {
        $allVisible = collect($this->collections)->pluck('group_key')->map(fn($v) => (string)$v)->values()->all();

        if (count($this->selectedGroups) === count($allVisible)) {
            // الكل محدد → الغِ تحديد الظاهر
            $this->selectedGroups = array_values(array_diff($this->selectedGroups, $allVisible));
        } else {
            // حدّد كل الظاهر + حافظ على الموجود مسبقًا (بدون تكرار)
            $this->selectedGroups = array_values(array_unique(array_merge(
                array_diff($this->selectedGroups, []),
                $allVisible
            )));
        }
        $this->clearToast(); // ← لا تعيد إظهار التوست بعد التبديل
    }
    /** يطبع النص العربي للمقارنة: يحذف التمديد ويوحّد الألف/الياء/التاء المربوطة والهمزات */
    protected function normalize(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';

        // حذف الكشيدة
        $s = str_replace('ـ', '', $s);

        // توحيد الألف والهمزات والياء والتاء المربوطة
        $map = [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ى' => 'ي', 'ئ' => 'ي',
            'ة' => 'ه',
            'ؤ' => 'و',
            'ء' => '',   // أحياناً تشويش
        ];
        $s = strtr($s, $map);

        // تحويل إلى حروف صغيرة (يدعم العربية)
        return mb_strtolower($s);
    }
// يزيل الزيادات مثل: "لمجموعة #<UUID> (سجل #12)" من الملاحظات المعروضة فقط
protected function sanitizeCollectionNote(?string $note): string
{
    $n = (string)($note ?? '');

    // احذف "لمجموعة #<uuid>"
    $n = preg_replace('/\s*لمجموعة\s*#?[0-9a-fA-F-]{6,}\s*/u', ' ', $n);

    // احذف "(سجل #123)" أو "(سجّل #123)"
    $n = preg_replace('/\s*\((?:سجل|سجّل)\s*#\d+\)\s*/u', ' ', $n);

    // نظّف المسافات والرموز المتبقية في النهاية
    $n = trim(preg_replace('/\s{2,}/u', ' ', $n));
    $n = preg_replace('/[\-\–:\.،]\s*$/u', '', $n);

    return trim($n);
}

    public function render()
    {
        return view('livewire.agency.customer-invoice-overview', [
            'customer'    => $this->customer,
            'collections' => $this->collections,
        ])->layout('layouts.agency');
    }

    public function resetFilters(): void
    {
        $this->search   = '';
        $this->fromDate = '';
        $this->toDate   = '';

        $this->applyFilters();
        $this->clearToast(); // ← لا تعيد إظهار التوست بعد التبديل
    }

  

    public function exportSingle(string $groupKey)
{
    $this->clearToast();
    $payload = base64_encode(json_encode([(string)$groupKey]));

    return redirect()->route('agency.customer-invoices-pdf.print', [
        'customer' => $this->customer->id,
        'sel'      => $payload, // أرسل المجموعة المفردة فقط
    ]);
}

public function exportSelected()
{
    $this->clearToast();
    if (empty($this->selectedGroups)) {
        $this->dispatch('notify', type: 'warning', message: 'اختر مستفيدًا واحدًا على الأقل.');
        return;
    }

    $payload = base64_encode(json_encode($this->selectedGroups));

    return redirect()->route('agency.customer-invoices-bulk.print', [
        'customer' => $this->customer->id,
        'sel'      => $payload,
    ]);
}



// App/Livewire/Agency/CustomerInvoiceOverview.php

// حالة الضريبة (فردي)
public bool $showSingleTaxModal = false;
public string $singleTaxGroupKey = '';
public string $singleTaxAmount = '';
public bool $singleTaxIsPercent = true;

// حالة الضريبة (مجمّع)
public bool $showBulkTaxModal = false;
public string $bulkTaxAmount = '';
public bool $bulkTaxIsPercent = true;
public float $bulkSubtotal = 0.0;

// افتح مودال فردي
public function askSingleTax(string $groupKey)
{
    $this->clearToast(); 
    $this->singleTaxGroupKey = (string)$groupKey;
    $this->singleTaxAmount   = '';
    $this->singleTaxIsPercent = true;
    $this->showSingleTaxModal = true;
}

public function confirmSingleTax()
{
    $this->clearToast();
    $amt = (float)($this->singleTaxAmount ?: 0);
    $payload = base64_encode(json_encode([(string)$this->singleTaxGroupKey]));
    $this->showSingleTaxModal = false;

    return redirect()->route('agency.customer-invoices-pdf.print', [
        'customer'   => $this->customer->id,
        'sel'        => $payload,
        'tax'        => $amt,
        'is_percent' => $this->singleTaxIsPercent ? 1 : 0,
    ]);
}



    // افتح مودال مجمّع
    public function askBulkTax()
    {
        if (empty($this->selectedGroups)) {
            $this->toastType = 'error';
            $this->toastMessage = 'اختر مستفيدًا واحدًا على الأقل لإصدار فاتورة مجمّعة.';
            return;
        }
        $this->clearToast();

        // احسب Subtotal تقريبي من العناصر الظاهرة المختارة
        $this->bulkSubtotal = collect($this->collections)
            ->filter(fn($r)=>in_array((string)$r->group_key, $this->selectedGroups, true))
            ->sum(fn($r)=>(float)($r->usd_sell ?? $r->net_total ?? 0)); // توافقًا مع بنائك
        $this->bulkTaxAmount   = '';
        $this->bulkTaxIsPercent = true;
        $this->showBulkTaxModal = true;
    }

    public function updatedSelectedGroups()
    {
        $this->clearToast();
    }

public function confirmBulkTax()
{
    $this->clearToast();

    if (empty($this->selectedGroups)) {
        $this->toastType = 'error';
        $this->toastMessage = 'اختر مستفيدًا واحدًا على الأقل.';
        return;
    }

    $payload = base64_encode(json_encode(array_values($this->selectedGroups)));
    $this->showBulkTaxModal = false;

    return redirect()->route('agency.customer-invoices-bulk.print', [
        'customer'   => $this->customer->id,
        'sel'        => $payload,
        'tax'        => (float)($this->bulkTaxAmount ?: 0),
        'is_percent' => $this->bulkTaxIsPercent ? 1 : 0,
    ]);
}




}
