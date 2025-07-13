<?php

namespace App\Livewire\Sales;

use App\Models\Provider;
use App\Models\Sale;
use App\Models\ServiceType;
use App\Models\Intermediary;
use App\Models\Customer;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $beneficiary_name, $sale_date, $service_item_id, $provider_id,
    $intermediary_id, $usd_buy, $usd_sell, $commission, $route, $pnr, $reference,
    $status, $amount_paid, $depositor_name, $account_id, $customer_id, $sale_profit = 0,
    $payment_method, $payment_type, $receipt_number, $phone_number ,$service_type_id;


    public $editingSale = null;
    public $currency;
    public $totalAmount = 0;           // إجمالي البيع
    public $totalReceived = 0;         // ما تم تحصيله
    public $totalPending = 0;          // المبالغ الآجلة
    public $totalProfit = 0;           // إجمالي الربح
    public $amount_due = 0; // المبلغ المتبقي
    public $services = []; // أضف هذا الخاصية

    // في دالة mount أو مكان مناسب
    public function fetchServices()
    {
        $this->services = \App\Models\DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة الخدمات');
        })->get();
    }

    public function mount()
    {
        $this->currency = auth()->user()->agency->currency ?? 'USD';
        $this->sale_date = now()->format('Y-m-d'); // تاريخ اليوم كافتراض
        $this->fetchServices(); // ✅ صحيح

             }



    protected function rules()
    {
        return [
            'beneficiary_name' => 'nullable|string|max:255',
            'sale_date' => 'required|date',
            'service_type_id' => 'required|exists:dynamic_list_items,id',
            'provider_id' => 'nullable|exists:providers,id',
            'intermediary_id' => 'nullable|exists:intermediaries,id',
            'usd_buy' => 'nullable|numeric',
            'usd_sell' => 'nullable|numeric',
            'commission' => 'nullable|numeric',
            'route' => 'nullable|string',
            'pnr' => 'nullable|string',
            'reference' => 'nullable|string',
            'status' => 'nullable|string',
            'amount_paid' => 'nullable|numeric',
            'depositor_name' => 'nullable|string',
            'account_id' => 'nullable|exists:accounts,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_profit' => 'nullable|numeric',
            'payment_method' => 'nullable|string',
            'payment_type' => 'nullable|string',
            'receipt_number' => 'nullable|string',
            'phone_number' => 'nullable|string',
        ];
    }


    public function save()
    {
        $this->validate();

        Sale::create([
            'beneficiary_name' => $this->beneficiary_name,
            'sale_date' => $this->sale_date,
            'service_type_id' => $this->service_type_id,
            'provider_id' => $this->provider_id,
            'intermediary_id' => $this->intermediary_id,
            'usd_buy' => $this->usd_buy,
            'usd_sell' => $this->usd_sell,
            'commission' => $this->commission,
            'route' => $this->route,
            'pnr' => $this->pnr,
            'reference' => $this->reference,
            'status' => $this->status,
            'amount_paid' => $this->amount_paid,
            'depositor_name' => $this->depositor_name,
            'account_id' => $this->account_id,
            'customer_id' => $this->customer_id,
            'sale_profit' => $this->sale_profit,
            'payment_method' => $this->payment_method,
            'payment_type' => $this->payment_type,
            'receipt_number' => $this->receipt_number,
            'phone_number' => $this->phone_number,
            'user_id' => Auth::id(),
            'agency_id' => Auth::user()->agency_id,
        ]);


        $this->resetForm();
        session()->flash('message', 'تمت إضافة العملية بنجاح');
    }

    public function duplicate($id)
    {
        $sale = Sale::findOrFail($id);

        $this->beneficiary_name = $sale->beneficiary_name;
        $this->sale_date = $sale->sale_date;
        $this->service_type_id = $sale->service_type_id;
        $this->provider_id = $sale->provider_id;
        $this->intermediary_id = $sale->intermediary_id;
        $this->usd_buy = $sale->usd_buy;
        $this->usd_sell = $sale->usd_sell;
        $this->commission = $sale->commission;
        $this->route = $sale->route;
        $this->pnr = $sale->pnr;
        $this->reference = $sale->reference;
        $this->status = $sale->status;
        $this->amount_paid = $sale->amount_paid;
        $this->depositor_name = $sale->depositor_name;
        $this->account_id = $sale->account_id;
        $this->customer_id = $sale->customer_id;
        $this->sale_profit = $sale->sale_profit;
        $this->payment_method = $sale->payment_method;
        $this->payment_type = $sale->payment_type;
        $this->receipt_number = $sale->receipt_number;
        $this->phone_number = $sale->phone_number;
    }


    public function resetForm()
    {
        $this->reset([
            'beneficiary_name',
            'sale_date',
            'service_type_id',
            'provider_id',
            'intermediary_id',
            'usd_buy',
            'usd_sell',
            'commission',
            'route',
            'pnr',
            'reference',
            'status',
            'amount_paid',
            'depositor_name',
            'account_id',
            'customer_id',
            'sale_profit',
            'payment_method',
            'payment_type',
            'receipt_number',
            'phone_number'
        ]);

    }
 
    public function resetFields()
    {
        $this->resetForm();
    }
    public function updatedServiceItemId()
    {
        $this->provider_id = null; // إعادة تعيين المزود المختار
    }
    public function getFilteredProviders()
{
    $query = Provider::query();

    if ($this->service_item_id) {
        $query->where('service_item_id', $this->service_item_id);
    }

    return $query->get();
}

public function render()
{
    $sales = Sale::with(['user', 'provider', 'service', 'customer', 'account'])->latest()->paginate(10);
    
    $services = \App\Models\DynamicListItem::whereHas('list', function ($query) {
        $query->where('name', 'قائمة الخدمات');
    })->get();
    
    // جلب المزودين بناءً على نوع الخدمة المحدد
    $providers = Provider::query();
    
    if ($this->service_item_id) {
        $providers->where('service_item_id', $this->service_item_id);
    }
    
    $providers = $providers->get();
    
    $intermediaries = Intermediary::all();
    $customers = Customer::all();
    $accounts = Account::all();
    
    $salesQuery = Sale::where('agency_id', Auth::user()->agency_id)
        ->whereDate('sale_date', now()->toDateString());

    // إجمالي البيع = مجموع usd_sell
    $this->totalAmount = $salesQuery->sum('usd_sell');

    // المبلغ المحصل = amount_paid
    $this->totalReceived = $salesQuery->sum('amount_paid');

    // الآجل = إجمالي البيع - المحصل
    $this->totalPending = $this->totalAmount - $this->totalReceived;

    // الربح الإجمالي
    $this->totalProfit = $salesQuery->sum('sale_profit');

    return view('livewire.sales.index', [
        'sales' => $sales,
        'services' => $services, // تم تغيير الاسم من serviceTypes إلى services
        'providers' => $providers,
        'intermediaries' => $intermediaries,
        'customers' => $customers,
        'accounts' => $accounts
    ])->layout('layouts.agency');
}



    public function calculateProfit()
    {
        if (is_numeric($this->usd_buy) && is_numeric($this->usd_sell)) {
            $this->sale_profit = $this->usd_sell - $this->usd_buy;
        } else {
            $this->sale_profit = 0;
        }
    }



    public function updatedUsdSell()
    {
        $this->calculateDue();
        $this->calculateProfit();
    }

    public function calculateDue()
    {
        if (is_numeric($this->usd_sell) && is_numeric($this->amount_paid)) {
            $this->amount_due = round($this->usd_sell - $this->amount_paid, 2);
        } else {
            $this->amount_due = 0;
        }
    }
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['usd_buy', 'usd_sell'])) {
            $this->calculateProfit();
            $this->calculateDue();
        }

        if ($propertyName === 'amount_paid') {
            $this->calculateDue();
        }
    }



}
