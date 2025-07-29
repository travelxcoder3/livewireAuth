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

public function mount($sale)
{
    $this->sale = Sale::with([
        'customer',
        'collections.customerType',
        'collections.debtType',
        'collections.customerResponse',
        'collections.customerRelation',
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
    ];
})->values();
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
        return view('livewire.agency.show-collection-details')
            ->layout('layouts.agency');
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

        if ($totalToPay > $this->remainingAmount) {
            $this->addError('amount', 'المبلغ الكلي يتجاوز المتبقي!');
            return;
        }

        if ($totalToPay <= 0) {
            $this->addError('amount', 'لا يوجد مبلغ لتحصيله.');
            return;
        }

        \App\Models\Collection::create([
            'agency_id' => $this->sale->agency_id,
            'sale_id' => $this->sale->id,
            'amount' => $totalToPay,
            'payment_date' => now(),
            'note' => 'تحصيل تلقائي لباقي المبلغ.',
        ]);

        // تحديث البيانات بعد الحفظ
        $this->sale->refresh();
        $this->calculateAmounts();
        
        $this->showEditModal = false;
        $this->dispatch('payment-collected');
        $this->dispatch('amountsUpdated'); // إرسال حدث لتحديث الواجهة
        session()->flash('message', 'تم تسجيل التحصيل بنجاح.');
    }

    public function cancelEdit()
    {
        $this->reset(['showEditModal', 'services', 'payRemainingNow']);
    }
}