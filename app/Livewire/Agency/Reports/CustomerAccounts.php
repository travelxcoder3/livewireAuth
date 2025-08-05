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
use Livewire\WithPagination;

#[Layout('layouts.agency')]
class CustomerAccounts extends Component
{
    use WithPagination;
    public $clientName = '';
    public $customerTypeId = '';
    public $fromDate = '';
    public $toDate = '';
    public $accountTypeFilter = '';

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
        $agencyId = Auth::user()->agency_id;
        $from = $this->fromDate ? Carbon::parse($this->fromDate)->startOfDay() : null;
        $to = $this->toDate ? Carbon::parse($this->toDate)->endOfDay() : null;

        // فلترة العملاء حسب الاسم
        $customersQuery = Customer::where('agency_id', $agencyId);

        if (trim($this->clientName) !== '') {
            $customersQuery->where('name', 'like', '%' . trim($this->clientName) . '%');
        }

        if (!empty($this->accountTypeFilter)) {
            $customersQuery->where('account_type', $this->accountTypeFilter);
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
            $customerCollections = $collections->filter(fn($c) => $c->sale?->customer_id === $customer->id);

            // ⚠️ هذا السطر يحدد آخر عملية بيع بناءً على ID كما طلبت
            $lastSale = $customerSales->sortByDesc('id')->first();
            $lastSaleDate = $lastSale && $lastSale->sale_date ? Carbon::parse($lastSale->sale_date) : null;

            // تطبيق الفلاتر بالتاريخ على تاريخ آخر عملية بيع
            if (
                ($from && (!$lastSaleDate || $lastSaleDate->lt($from))) ||
                ($to && (!$lastSaleDate || $lastSaleDate->gt($to)))
            ) {
                return null;
            }

            // ✅ منطق "له" و"عليه" بالتجميع حسب group
            $groupedSales = $customerSales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

            $totalCustomerOwes = 0;
            $rawCredit = 0;

            foreach ($groupedSales as $group) {
                $remaining = $group->sum(fn($s) => $s->usd_sell - $s->amount_paid - $s->collections->sum('amount'));

                if ($remaining > 0) {
                    $totalCustomerOwes += $remaining;
                } elseif ($remaining < 0) {
                    $rawCredit += abs($remaining);
                }
            }

            // خصم ما تم استخدامه لتسديد عملاء آخرين
            $usedCredit = \App\Models\Collection::whereHas('sale', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')->sum('amount');

            $totalCompanyOwes = max(0, $rawCredit - $usedCredit);
            $netBalance = $totalCustomerOwes - $totalCompanyOwes;

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'currency' => Auth::user()->agency->currency ?? 'USD',
                'total' => $customerSales->sum('usd_sell'),
                'paid' => $customerSales->sum('amount_paid'),
                'collected' => $customerCollections->sum('amount'),
                'refunded' => 0,
                'net_balance' => $netBalance,
                // ⚖️ "عليه" = المتبقي على العميل = totalAmount - المدفوع - المحصل
                'remaining_for_customer' => $totalCustomerOwes,
                // ⚖️ "له" = المتبقي للعميل = العمليات الزائدة - ما تم استخدامه
                'remaining_for_company' => $totalCompanyOwes,
                'last_sale_date' => $lastSaleDate,
                'account_type' => $customer->account_type,
            ];
        })->filter()->values();
        $totalRemainingForCustomer = $customers->sum('remaining_for_customer'); // عليه
        $totalRemainingForCompany = $customers->sum('remaining_for_company'); // له

        return view('livewire.agency.reportsView.customer-accounts', [
            'customers' => $customers,
            'columns' => CustomerAccountsTable::columns(),
            'totalRemainingForCustomer' => $totalRemainingForCustomer,
            'totalRemainingForCompany' => $totalRemainingForCompany,
        ]);
    }
}
