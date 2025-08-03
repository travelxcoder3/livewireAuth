<?php

namespace App\Livewire\Agency;

use App\Models\Sale;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountHistories extends Component
{
    public $selectedCustomerId = null;
    public $collections = [];

    public Collection $rawCustomers;

    public function mount()
    {
        // ✅ جلب المبيعات + العملاء + التحصيلات
        $allSales = Sale::with(['customer', 'collections'])
            ->where('agency_id', Auth::user()->agency_id)
            ->get();

        // ✅ تجميع حسب العميل
        $groupedByCustomer = $allSales->groupBy('customer_id');

        // ✅ تحويل إلى كولكشن منظمة
        $this->rawCustomers = $groupedByCustomer->map(function ($sales) {
            $customer = $sales->first()?->customer;
if (!$customer) return null;

            $groupedByGroup = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

            $totalDue = $groupedByGroup->sum(function ($group) {
                $total = $group->sum('usd_sell');
                $paid = $group->sum('amount_paid');
                $collected = $group->flatMap->collections->sum('amount');
                return $total - $paid - $collected;
            });

            return (object) [
                'id' => $customer->id,
                'name' => $customer->name,
                'total_due' => $totalDue,
            ];
        })->filter()->values(); // ⬅ استبعاد الفارغ
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomerId = $customerId;

        // جلب كل المبيعات للعميل
        $sales = Sale::with(['collections', 'employee', 'serviceType'])
            ->where('agency_id', Auth::user()->agency_id)
            ->where('customer_id', $customerId)
            ->get();

        // تجميع حسب group_id أو id
        $grouped = $sales->groupBy(fn($sale) => $sale->sale_group_id ?? $sale->id);

        $this->collections = $grouped->map(function ($sales) {
            $first = $sales->first();

            return (object)[
                'beneficiary_name' => $first->beneficiary_name,
                'sale_date' => $first->sale_date,
                'usd_sell' => $sales->sum('usd_sell'),
                'amount_paid' => $sales->sum('amount_paid'),
                'collected' => $sales->flatMap->collections->sum('amount'),
                'total_paid' => $sales->sum('amount_paid') + $sales->flatMap->collections->sum('amount'),
                'remaining' => $sales->sum('usd_sell') - ($sales->sum('amount_paid') + $sales->flatMap->collections->sum('amount')),
                'note' => $sales->flatMap->collections->last()?->note ?? '-',
            ];
        })->values();
    }

    public function render()
    {
        $perPage = 10;
        $page = request()->get('page', 1);

        $collections = collect($this->collections);

        $paginatedCollections = new LengthAwarePaginator(
            $collections->forPage($page, $perPage),
            $collections->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $paginatedCustomers = new LengthAwarePaginator(
            $this->rawCustomers->forPage($page, $perPage),
            $this->rawCustomers->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.agency.account-histories', [
            'customers' => $paginatedCustomers,
            'collections' => $paginatedCollections,
            'selectedCustomerId' => $this->selectedCustomerId,
        ])->layout('layouts.agency');
    }
}
