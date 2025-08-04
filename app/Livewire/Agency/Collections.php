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
    $allSales = Sale::with([
        'customer',
        'collections' => function ($q) {
            $q->latest();
        },
        'collections.customerType',
        'collections.debtType',
        'collections.customerResponse',
        'collections.customerRelation'
    ])
        ->where('agency_id', Auth::user()->agency_id)
        ->where('user_id', Auth::id()) // ✅ عرض فقط عمليات الموظف الحالي
    
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
logger()->info('تجميع العمليات لكل عميل', [
    'count' => $groupedByCustomer->count(),
    'ids' => $groupedByCustomer->keys()->toArray(),
]);

  $customers = $groupedByCustomer->map(function ($sales, $customerId) {
    $firstSale = $sales->first();

if (!$firstSale || !$firstSale->customer) {
    return null;
}

$customer = $firstSale->customer;


    $groupedByGroup = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

$totalCustomerOwes = 0;
$totalCompanyOwes = 0;

// حساب الرصيد الفعلي من العمليات
$rawCredit = 0;

foreach ($groupedByGroup as $group) {
    $remaining = $group->sum(fn($s) => $s->usd_sell - $s->amount_paid - $s->collections->sum('amount'));

    if ($remaining > 0) {
        $totalCustomerOwes += $remaining;
    } elseif ($remaining < 0) {
        $rawCredit += abs($remaining);
    }
}

// طرح ما تم استخدامه لتسديد عملاء آخرين
$usedCredit = \App\Models\Collection::whereHas('sale', function($q) use ($customerId) {
        $q->where('customer_id', $customerId);
    })
    ->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')
    ->sum('amount');

$totalCompanyOwes = max(0, $rawCredit - $usedCredit);



    // ✅ إذا لم يكن عليه شيء إطلاقًا (صفر) نهمل السطر
    if ($totalCustomerOwes == 0 && $totalCompanyOwes == 0) return null;

    $latestCollection = $sales->flatMap->collections->sortByDesc('payment_date')->first();

return (object) [
    'id' => $customer->id,
    'name' => $customer->name,
    'remaining_for_customer' => $totalCustomerOwes,
    'remaining_for_company' => $totalCompanyOwes,
    'net_due' => $totalCustomerOwes - $totalCompanyOwes,
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
