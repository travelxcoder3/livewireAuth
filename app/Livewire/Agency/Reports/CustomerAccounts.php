<?php

namespace App\Livewire\Agency\Reports;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Collection;
use App\Models\DynamicListItemSub;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Tables\CustomerAccountsTable;
use Carbon\Carbon;

#[Layout('layouts.agency')]
class CustomerAccounts extends Component
{
    public $clientName = '';
    public $customerTypeId = '';
    public $fromDate = '';
    public $toDate = '';
    public $customerTypes = [];

    public function mount()
    {
        $this->customerTypes = DynamicListItemSub::whereIn('id', Collection::whereNotNull('customer_type_id')->pluck('customer_type_id')->unique())->get();
    }

    public function updatedClientName()
    {
        $this->resetPage(); // لتحديث النتائج عند تعديل الاسم
    }
    public function resetFilters()
    {
        $this->fromDate = null;
        $this->toDate = null;
        $this->clientName = '';
        $this->customerTypeId = null;
    }
    public function render()
    {
        logger('🔍 فلترة الاسم بعد trim: [' . trim($this->clientName) . ']');

        $agencyId = Auth::user()->agency_id;
        $from = $this->fromDate ? Carbon::parse($this->fromDate)->startOfDay() : null;
        $to = $this->toDate ? Carbon::parse($this->toDate)->endOfDay() : null;

        // فلترة العملاء حسب الاسم
        $customersQuery = Customer::where('agency_id', $agencyId);

        if (trim($this->clientName) !== '') {
            $customersQuery->where('name', 'like', '%' . trim($this->clientName) . '%');
        }

        if (!empty($this->customerTypeId)) {
            $customersQuery->whereHas('sales.collections', function ($q) {
                $q->where('customer_type_id', $this->customerTypeId);
            });
        }

        $filteredCustomers = $customersQuery->get();
        $customerIds = $filteredCustomers->pluck('id');

        // العمليات والتحصيلات الخاصة فقط
        $sales = Sale::where('agency_id', $agencyId)
            ->whereIn('customer_id', $customerIds)
            ->get();

        $collections = Collection::where('agency_id', $agencyId)
            ->whereHas('sale', function ($q) use ($customerIds) {
                $q->whereIn('customer_id', $customerIds);
            })
            ->get();

        $customers = $filteredCustomers->map(function ($customer) use ($sales, $collections, $from, $to) {
            $customerSales = $sales->where('customer_id', $customer->id);
            $lastSale = $customerSales->sortByDesc('id')->first();
            $lastSaleDate = $lastSale && $lastSale->sale_date ? Carbon::parse($lastSale->sale_date) : null;

            if (
                ($from && (!$lastSaleDate || $lastSaleDate->lt($from))) ||
                ($to && (!$lastSaleDate || $lastSaleDate->gt($to)))
            ) {
                return null;
            }

            $customerCollections = $collections->filter(fn($c) => $c->sale?->customer_id === $customer->id);

            $totalSell = $customerSales->sum('usd_sell');
            $totalPaid = $customerSales->sum('amount_paid');
            $totalRefund = $customerSales->whereIn('status', ['refunded', 'void', 'canceled'])->sum('amount_paid');
            $totalCollected = $customerCollections->sum('amount');
            $netBalance = $totalSell - $totalPaid - $totalCollected;

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'currency' => 'USD',
                'total' => $totalSell,
                'paid' => $totalPaid,
                'collected' => $totalCollected,
                'refunded' => $totalRefund,
                'net_balance' => $netBalance,
                'remaining_for_customer' => max(0, $netBalance),
                'remaining_for_company' => max(0, $totalRefund - $totalCollected),
                'last_sale_date' => $lastSaleDate,
            ];
        })->filter()->values();

        return view('livewire.agency.reportsView.customer-accounts', [
            'customers' => $customers,
            'columns' => CustomerAccountsTable::columns(),
        ]);
    }

}
