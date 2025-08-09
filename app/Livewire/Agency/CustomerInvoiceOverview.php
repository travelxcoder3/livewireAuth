<?php

namespace App\Livewire\Agency;

use App\Models\Customer;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

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
            $s = $sales->first();
            $collected = $sales->sum(fn($x) => $x->collections->sum('amount'));
            $paid      = $sales->sum('amount_paid');
            $total     = $collected + $paid;

            return (object) [
                'group_key'        => (string) ($s->sale_group_id ?? $s->id),
                'beneficiary_name' => $s->beneficiary_name,
                'sale_date'        => $s->sale_date,                 // 'YYYY-MM-DD'
                'service_label'    => $s->service->label ?? '-',
                'usd_sell'         => $s->usd_sell,
                'amount_paid'      => $paid,
                'collected'        => $collected,
                'total_paid'       => $total,
                'remaining'        => $s->usd_sell - $total,
                'note'             => $s->note,

                // تفاصيل المراحل (السيناريوهات)
                'scenarios' => $sales->map(function ($sale) {
                    return [
                        'date'        => $sale->sale_date,
                        'usd_sell'    => $sale->usd_sell,
                        'amount_paid' => $sale->amount_paid,
                        'status'      => $sale->status,
                        'note'        => $sale->reference ?? '-',
                    ];
                }),

                // سجل التحصيلات
                'collections' => $sales->flatMap->collections->map(function ($col) {
                    return [
                        'amount'       => $col->amount,
                        'payment_date' => $col->payment_date,
                        'note'         => $col->note,
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
    }

    public function showDetails($index)
    {
        $this->activeSale = $this->collections[$index];
    }

    public function closeModal()
    {
        $this->activeSale = null;
    }

    // ✅ يوجّه لراوت الطباعة مع تحميل المجموعات المختارة Base64
    public function exportSelected()
    {
        if (empty($this->selectedGroups)) {
            $this->dispatch('notify', type: 'warning', message: 'اختر مستفيدًا واحدًا على الأقل.');
            return;
        }

        $payload = base64_encode(json_encode($this->selectedGroups));
        return redirect()->route('agency.customer-invoices.print', [
            'customer' => $this->customer->id,
            'sel'      => $payload,
        ]);
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

        // لو تحب تلغي التحديد بعد التنظيف (اختياري)
        // $this->selectedGroups = [];

        $this->applyFilters();
    }

}
