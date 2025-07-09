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

    public $beneficiary_name, $sale_date, $service_type_id, $provider_id,
           $intermediary_id, $usd_buy, $usd_sell, $note, $route, $pnr, $reference,
           $action, $amount_received, $depositor_name, $account_id, $customer_id, $sale_profit=0;
    
    public $editingSale = null;
    public $currency;
    public $totalAmount = 0;           // إجمالي البيع
    public $totalReceived = 0;         // ما تم تحصيله
    public $totalPending = 0;          // المبالغ الآجلة
    public $totalProfit = 0;           // إجمالي الربح
    public $amount_due = 0; // المبلغ المتبقي


public function mount()
{
    $this->currency = auth()->user()->agency->currency ?? 'USD';
    $this->sale_date = now()->format('Y-m-d'); // تاريخ اليوم كافتراض
}



    protected function rules()
    {
        return [
            'beneficiary_name' => 'nullable|string|max:255',
            'sale_date' => 'required|date',
            'service_type_id' => 'required|exists:service_types,id',
            'provider_id' => 'nullable|exists:providers,id',
            'intermediary_id' => 'nullable|exists:intermediaries,id',
            'usd_buy' => 'nullable|numeric',
            'usd_sell' => 'nullable|numeric',
            'note' => 'nullable|string',
            'route' => 'nullable|string',
            'pnr' => 'nullable|string',
            'reference' => 'nullable|string',
            'action' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'depositor_name' => 'nullable|string',
            'account_id' => 'nullable|exists:accounts,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_profit' => 'nullable|numeric',
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
            'note' => $this->note,
            'route' => $this->route,
            'pnr' => $this->pnr,
            'reference' => $this->reference,
            'action' => $this->action,
            'amount_received' => $this->amount_received,
            'depositor_name' => $this->depositor_name,
            'account_id' => $this->account_id,
            'customer_id' => $this->customer_id,
            'sale_profit' => $this->sale_profit,
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
        $this->note = $sale->note;
        $this->route = $sale->route;
        $this->pnr = $sale->pnr;
        $this->reference = $sale->reference;
        $this->action = $sale->action;
        $this->amount_received = $sale->amount_received;
        $this->depositor_name = $sale->depositor_name;
        $this->account_id = $sale->account_id;
        $this->customer_id = $sale->customer_id;
        $this->sale_profit = $sale->sale_profit;
    }

    public function resetForm()
    {
        $this->reset([
            'beneficiary_name', 'sale_date', 'service_type_id', 'provider_id',
            'intermediary_id', 'usd_buy', 'usd_sell', 'note', 'route', 'pnr',
            'reference', 'action', 'amount_received', 'depositor_name',
            'account_id', 'customer_id', 'sale_profit'
        ]);
    }

    public function resetFields()
    {
        $this->resetForm();
    }

    public function render()
    {
        $sales = Sale::with(['user', 'provider', 'serviceType', 'customer', 'account'])->latest()->paginate(10);
        $serviceTypes = ServiceType::all();
        $providers = Provider::all();
        $intermediaries = Intermediary::all();
        $customers = Customer::all();
        $accounts = Account::all();
        //$salesQuery = Sale::where('agency_id', Auth::user()->agency_id);
        $salesQuery = Sale::where('agency_id', Auth::user()->agency_id)
            ->whereDate('sale_date', now()->toDateString());

        // إجمالي البيع = مجموع usd_sell
        $this->totalAmount = $salesQuery->sum('usd_sell');

        // المبلغ المحصل = amount_received
        $this->totalReceived = $salesQuery->sum('amount_received');

        // الآجل = إجمالي البيع - المحصل
        $this->totalPending = $this->totalAmount - $this->totalReceived;

        // الربح الإجمالي
        $this->totalProfit = $salesQuery->sum('sale_profit');

        return view('livewire.sales.index', compact('sales', 'serviceTypes', 'providers', 'intermediaries', 'customers', 'accounts'))
            ->layout('layouts.agency');
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
            if (is_numeric($this->usd_sell) && is_numeric($this->amount_received)) {
                $this->amount_due = round($this->usd_sell - $this->amount_received, 2);
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

        if ($propertyName === 'amount_received') {
            $this->calculateDue(); 
        }
    }



}