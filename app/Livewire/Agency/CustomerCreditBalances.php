<?php
namespace App\Livewire\Agency;

use App\Models\Sale;
use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Tables\CustomerCreditBalancesTable;

class CustomerCreditBalances extends Component
{
    public $search = '';
    // في الكلاس
public $perPage = 10;

// في الدالة render()

public function render()
{
    $allSales = Sale::with(['customer', 'collections'])
    ->where('agency_id', Auth::user()->agency_id)
    ->whereNotNull('customer_id')
    ->whereHas('customer') // تأكد أن العلاقة موجودة
    ->get();


    $groupedByCustomer = $allSales->groupBy('customer_id');

   $customers = $groupedByCustomer->map(function ($sales, $customerId) {
    if (is_null($customerId)) {
        return null;
    }

    $firstSale = $sales->first();
    $customer  = $firstSale?->customer;

    if (!$customer) {
        return null;
    }

    $groupedByGroup = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

    $rawCredit = 0;
    foreach ($groupedByGroup as $group) {
        $remaining = $group->sum(fn($s) => $s->usd_sell - $s->amount_paid - $s->collections->sum('amount'));
        if ($remaining < 0) {
            $rawCredit += abs($remaining);
        }
    }

    $usedCredit = \App\Models\Collection::whereHas('sale', function($q) use ($customerId) {
            $q->where('customer_id', $customerId);
        })
        ->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')
        ->sum('amount');

    $netCredit = $rawCredit - $usedCredit;

    if ($netCredit <= 0) return null;

    return (object)[
        'id'            => $customer->id,
        'name'          => $customer->name,
        'phone'         => $customer->phone,
        'credit_amount' => $netCredit,
    ];
})->filter()->values();


    if ($this->search) {
        $searchTerm = strtolower(trim($this->search));
        $customers = $customers->filter(function ($customer) use ($searchTerm) {
            return str_contains(strtolower($customer->name), $searchTerm) ||
                   str_contains($customer->phone, $searchTerm);
        });
    }

    $columns = \App\Tables\CustomerCreditBalancesTable::columns();

    return view('livewire.agency.customer-credit-balances', [
        'customers' => $customers->values(),
        'columns' => $columns,
    ])->layout('layouts.agency');
}

}
