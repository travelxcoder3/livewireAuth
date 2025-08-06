<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use App\Models\DynamicListItemSub;

class ShowCollectionDetails extends Component
{
    public $sale;
    public $services = [];
    public $saleId;
    public $totalAmount = 0;
    public $amountReceived = 0;
    public $remainingAmount = 0;
    public $showEditModal = false;
    public $paidAmount;
    public $paidFromSales = 0;
    public $paidFromCollections = 0;
    public $paidTotal = 0;
    public $payRemainingNow = 0;

    public $customerSales = [];
public $availableBalanceToPayOthers = 0;
public $payToCustomerList = [];
public $selectedPayCustomerId = null;

public function mount($sale)
{
    $this->sale = Sale::with([
        'customer',
        'collections.customerType',
        'collections.debtType',
        'collections.customerResponse',
        'collections.customerRelation',
        'collections.user', 
    ])
    ->where('agency_id', Auth::user()->agency_id)
    ->findOrFail($sale);

    $this->calculateAmounts();

    // ✅ جلب مبيعات العميل مجمعة حسب sale_group_id أو id إن لم يوجد group
   $rawSales = Sale::with(['employee', 'collections', 'serviceType'])
        ->where('agency_id', Auth::user()->agency_id)
        ->where('customer_id', $this->sale->customer_id)
        ->get();

    $grouped = $rawSales->groupBy(function ($item) {
        return $item->sale_group_id ?? $item->id;
    });


    $this->customerSales = $grouped->map(function ($sales) {
    $first = $sales->first();
    return (object)[
        'id' => $first->id,
        'group_id' => $first->sale_group_id,
        'employee' => $first->employee,
        'beneficiary_name' => $first->beneficiary_name,
        'service_date' => $first->service_date,
        'service_type_name' => optional($first->serviceType)->label,
         'sale_date' => $first->sale_date, 
        'service' => $first->service,
        'usd_sell' => $sales->sum('usd_sell'),
        'amount_paid' => $sales->sum('amount_paid'),
        'collections_total' => $sales->flatMap->collections->sum('amount'),
        'expected_payment_date' => $first->expected_payment_date,

    ];
})->values();
$this->availableBalanceToPayOthers = $this->customerSales->sum(function ($s) {
    $total = $s->usd_sell;
    $paid = $s->amount_paid;
    $collected = $s->collections_total;
    $remaining = $total - $paid - $collected;
    return $remaining < 0 ? abs($remaining) : 0;
});
$this->recalculateAvailableBalance();

}



  

    // دالة جديدة لحساب المبالغ
protected function calculateAmounts()
{
    // التحقق هل لدى العملية sale_group_id
    $groupId = $this->sale->sale_group_id;

    if ($groupId) {
        // جلب كل المبيعات بنفس sale_group_id لنفس الوكالة
        $groupedSales = Sale::with('collections')
            ->where('agency_id', Auth::user()->agency_id)
            ->where('sale_group_id', $groupId)
            ->get();

        $this->totalAmount = $groupedSales->sum('usd_sell');
        $this->paidFromSales = $groupedSales->sum('amount_paid');
        $this->paidFromCollections = $groupedSales->flatMap->collections->sum('amount');
    } else {
        // العملية مفردة بدون مجموعة
        $this->totalAmount = $this->sale->usd_sell ?? 0;
        $this->paidFromSales = $this->sale->amount_paid ?? 0;
        $this->paidFromCollections = $this->sale->collections->sum('amount');
    }

    $this->paidTotal = $this->paidFromSales + $this->paidFromCollections;
    $this->amountReceived = $this->paidTotal;
    $this->remainingAmount = $this->totalAmount - $this->paidTotal;
}



    public function render()
    {
return view('livewire.agency.show-collection-details', [
    'availableBalanceToPayOthers' => $this->availableBalanceToPayOthers,
])->layout('layouts.agency');

    }

    public function openEditAmountModal($saleId)
    {
        $this->sale = Sale::with('collections')->findOrFail($saleId);
        $this->calculateAmounts(); // استدعاء الدالة الجديدة

        if ($this->remainingAmount <= 0) {
            session()->flash('message', 'تم سداد كامل المبلغ، لا يمكن التحصيل.');
            return;
        }

        $agencyId = Auth::user()->agency_id;
        $this->services = DynamicListItemSub::whereHas('parentItem', function($q) use ($agencyId) {
            $q->whereHas('dynamicList', function($q) use ($agencyId) {
                $q->where('name', 'قائمة الخدمات')
                  ->where(function($q) use ($agencyId) {
                      $q->where('agency_id', $agencyId)
                        ->orWhereNull('agency_id');
                  });
            });
        })->get()->map(function ($service) {
            return ['id' => $service->id, 'name' => $service->label, 'amount' => 0, 'paid' => 0];
        })->toArray();

        $this->showEditModal = true;
    }
public function saveAmounts()
{
    $totalServiceAmount = collect($this->services)->sum('amount');
    $payAmount = $this->payRemainingNow ?? 0;
    $totalToPay = $totalServiceAmount + $payAmount;

    $maxAllowed = $this->isPayToOthersMode ? $this->availableBalanceToPayOthers : $this->remainingAmount;

    if ($totalToPay > $maxAllowed) {
        if ($this->isPayToOthersMode) {
            $this->addError('amount', 'المبلغ المدخل يتجاوز رصيد الشركة لدى العميل!');
        } else {
            $this->addError('amount', 'المبلغ الكلي يتجاوز المتبقي!');
        }
        return;
    }


    if ($totalToPay <= 0) {
        $this->addError('amount', 'لا يوجد مبلغ لتحصيله.');
        return;
    }

    if ($this->isPayToOthersMode) {
        // هنا التعديل المهم: حفظ كائن التحصيل في متغير أولاً
        $newCollection = \App\Models\Collection::create([
            'agency_id' => $this->sale->agency_id,
            'sale_id' => $this->saleId,
            'amount' => $totalToPay,
            'payment_date' => now(),
            'note' => 'تسديد من رصيد الشركة للعميل.',
            'user_id' => Auth::id(),
        ]);

        // ثم تمرير المتغير للدالة
        $this->linkRefundToSourceSales($newCollection, $totalToPay);
        $this->sale = $this->sale->fresh(['collections']);
    } else {
        \App\Models\Collection::create([
            'agency_id' => $this->sale->agency_id,
            'sale_id' => $this->sale->id,
            'amount' => $totalToPay,
            'payment_date' => now(),
            'note' => 'تحصيل تلقائي لباقي المبلغ.',
            'user_id' => Auth::id(),
        ]);
    }

    $this->sale->refresh();
    $this->calculateAmounts();
    $this->recalculateAvailableBalance();
    
    $this->showEditModal = false;
    session()->flash('message', 'تم تسجيل التحصيل بنجاح.');
    $this->isPayToOthersMode = false;
    $this->updateCustomerSalesList();

}

protected function updateCustomerSalesList()
{
    $rawSales = Sale::with(['employee', 'collections', 'serviceType'])
        ->where('agency_id', Auth::user()->agency_id)
        ->where('customer_id', $this->sale->customer_id)
        ->get();

    $grouped = $rawSales->groupBy(function ($item) {
        return $item->sale_group_id ?? $item->id;
    });

    $this->customerSales = $grouped->map(function ($sales) {
        $first = $sales->first();
        return (object)[
            'id' => $first->id,
            'group_id' => $first->sale_group_id,
            'employee' => $first->employee,
            'beneficiary_name' => $first->beneficiary_name,
            'service_date' => $first->service_date,
            'service_type_name' => optional($first->serviceType)->label,
            'sale_date' => $first->sale_date, 
            'service' => $first->service,
            'usd_sell' => $sales->sum('usd_sell'),
            'amount_paid' => $sales->sum('amount_paid'),
            'collections_total' => $sales->flatMap->collections->sum('amount'),
            'expected_payment_date' => $first->expected_payment_date,
        ];
    })->values();
}


protected function recalculateAvailableBalance()
{
    $this->availableBalanceToPayOthers = 0;

    foreach ($this->customerSales as $s) {
        $total = $s->usd_sell;
        $paid = $s->amount_paid;
        $collected = $s->collections_total;

        $remaining = $total - $paid - $collected;

        if ($remaining < 0) {
            $this->availableBalanceToPayOthers += abs($remaining);
        }
    }

    // إضافة هذا الجزء الجديد لحساب التحصيلات المستخدمة كتسديد للعملاء
    $usedForOthers = \App\Models\Collection::whereHas('sale', function($q) {
            $q->where('customer_id', $this->sale->customer_id);
        })
        ->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')
        ->sum('amount');

    $this->availableBalanceToPayOthers = max(0, $this->availableBalanceToPayOthers - $usedForOthers);
    
}




    public function cancelEdit()
    {
        $this->reset(['showEditModal', 'services', 'payRemainingNow']);
        $this->isPayToOthersMode = false;

    }
    public $isPayToOthersMode = false;

public function openPayToOthersModal()
{
    if ($this->availableBalanceToPayOthers <= 0) {
        session()->flash('error', 'لا يوجد رصيد للعميل.');
        return;
    }

    $this->payToCustomerList = collect($this->customerSales)
        ->filter(fn($s) => (($s->usd_sell - $s->amount_paid - $s->collections_total) > 0))
        ->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->beneficiary_name ?? 'غير معروف',
        ])
        ->values()
        ->toArray();

    $this->selectedPayCustomerId = null;
    $this->reset(['totalAmount', 'paidFromSales', 'paidFromCollections', 'paidTotal', 'amountReceived', 'remainingAmount', 'payRemainingNow']);

    $this->isPayToOthersMode = true;
    $this->showEditModal = true;
}
public function updatedSelectedPayCustomerId($value)
{
    $value = (int) $value;

    $sale = Sale::with(['collections'])
        ->where('agency_id', Auth::user()->agency_id)
        ->find($value);

    if ($sale) {
        // ✅ نربط العملية بالعميل الرئيسي
if (!$sale->customer_id || !$this->sale->customer_id || $sale->customer_id !== $this->sale->customer_id) {
    logger()->info('🔧 تعديل العملية لتربط بالعميل', [
        'sale_id' => $sale->id,
        'old_customer_id' => $sale->customer_id,
        'new_customer_id' => $this->sale->customer_id,
    ]);

    $sale->customer_id = $this->sale->customer_id;
    $sale->save();
}



        $this->sale = $sale;
        $this->saleId = $sale->id;

        $this->sale = $sale;
        $this->saleId = $sale->id;

        // استخدم نفس المنطق الموجود في calculateAmounts:
        $groupId = $sale->sale_group_id;

        if ($groupId) {
            $groupedSales = Sale::with('collections')
                ->where('agency_id', Auth::user()->agency_id)
                ->where('sale_group_id', $groupId)
                ->get();

            $this->totalAmount = $groupedSales->sum('usd_sell');
            $this->paidFromSales = $groupedSales->sum('amount_paid');
            $this->paidFromCollections = $groupedSales->flatMap->collections->sum('amount');
        } else {
            $this->totalAmount = $sale->usd_sell ?? 0;
            $this->paidFromSales = $sale->amount_paid ?? 0;
            $this->paidFromCollections = $sale->collections->sum('amount');
        }

        $this->paidTotal = $this->paidFromSales + $this->paidFromCollections;
        $this->remainingAmount = $this->totalAmount - $this->paidTotal;

        $this->payRemainingNow = min($this->remainingAmount, $this->availableBalanceToPayOthers);


        $this->payRemainingNow = min($this->remainingAmount, $this->availableBalanceToPayOthers);
    } else {
        $this->reset(['saleId', 'totalAmount', 'paidFromSales', 'paidFromCollections', 'paidTotal', 'remainingAmount', 'payRemainingNow']);
    }
}



protected function linkRefundToSourceSales($collection, $amountUsed)
{
    // الحصول على المبيعات التي لديها رصيد زائد (دين على الشركة)
    $salesWithCredit = collect($this->customerSales)->filter(function($s) {
        $remaining = $s->usd_sell - $s->amount_paid - $s->collections_total;
        return $remaining < 0;
    });

    if ($salesWithCredit->isNotEmpty()) {
        // تخزين معلومات العمليات المصدر في حقل الملاحظات
        $sourceNotes = $salesWithCredit->map(function($s) {
            return 'عملية #' . $s->id . ' (رصيد: ' . 
                   abs($s->usd_sell - $s->amount_paid - $s->collections_total) . ')';
        })->implode(' | ');

        $collection->update([
            'note' => $collection->note . " | تم السداد من: " . $sourceNotes
        ]);
    }
}

}