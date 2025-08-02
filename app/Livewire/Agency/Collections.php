<?php

namespace App\Livewire\Agency;

use App\Models\Sale;
use App\Models\DynamicListItemSub;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Collections extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $customerType = '';
    public $debtType = '';
    public $responseType = '';
    public $relationType = '';
    public $movementType = '';

 public function render()
{
    // ✅ جلب كل المبيعات المرتبطة بالعملاء مع علاقاتها
    $allSales = Sale::with(['customer', 'collections', 'collections.customerType', 'collections.debtType', 'collections.customerResponse', 'collections.customerRelation'])
        ->where('agency_id', Auth::user()->agency_id)
        ->when($this->search, fn($q) =>
            $q->whereHas('customer', fn($q2) =>
                $q2->where('name', 'like', "%{$this->search}%")
            )
        )
        ->when($this->startDate, fn($q) =>
            $q->whereDate('sale_date', '>=', $this->startDate)
        )
        ->when($this->endDate, fn($q) =>
            $q->whereDate('sale_date', '<=', $this->endDate)
        )
        ->get();

    // ✅ جمع المبيعات حسب العميل ثم حسب sale_group_id
    $groupedByCustomer = $allSales->groupBy('customer_id');

  $customers = $groupedByCustomer->map(function ($sales, $customerId) {
    $customer = $sales->first()->customer;

    $groupedByGroup = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

    $totalDue = $groupedByGroup->sum(function ($group) {
        $total = $group->sum('usd_sell');
        $paid = $group->sum('amount_paid');
        $collected = $group->flatMap->collections->sum('amount');
        return $total - $paid - $collected;
    });

    // ✅ إذا لم يكن عليه شيء إطلاقًا (صفر) نهمل السطر
    if ($totalDue == 0) return null;

    $latestCollection = $sales->flatMap->collections->sortByDesc('payment_date')->first();

    return (object) [
        'id' => $customer->id,
        'name' => $customer->name,
        'total_due' => abs($totalDue), // نعرض القيمة بدون إشارة فقط للعرض
        'due_type' => $totalDue > 0 ? 'مديون' : 'دائن', // جديد: يحدد نوع الحالة
        'last_payment' => optional($latestCollection)->payment_date,
        'customer_type' => optional($latestCollection?->customerType)->label ?? '-',
        'debt_type' => optional($latestCollection?->debtType)->label ?? '-',
        'customer_response' => optional($latestCollection?->customerResponse)->label ?? '-',
        'customer_relation' => optional($latestCollection?->customerRelation)->label ?? '-',
        'first_sale_id' => $sales->first()->id,
    ];
})->filter()->values();


    return view('livewire.agency.collections', [
        'sales' => $customers,
        'customerTypes' => $this->getOptions('نوع العميل'),
        'debtTypes' => $this->getOptions('نوع المديونية'),
        'responseTypes' => $this->getOptions('تجاوب العميل'),
        'relationTypes' => $this->getOptions('نوع ارتباطه بالشركة'),
    ])->layout('layouts.agency');
}



    protected function getOptions($label)
    {
        return DynamicListItemSub::whereHas('parentItem', fn($q) =>
            $q->where('label', $label)
        )->get();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->customerType = '';
        $this->debtType = '';
        $this->responseType = '';
        $this->relationType = '';
        $this->movementType = '';
    }
}
