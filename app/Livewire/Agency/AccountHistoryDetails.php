<?php

namespace App\Livewire\Agency;

use App\Models\Customer;
use Livewire\Component;

class AccountHistoryDetails extends Component
{
    public Customer $customer;
    public $collections;
    public $activeSale = null;

    public function mount(Customer $customer)
    {
        $this->customer = $customer;

        $sales = $customer->sales()->with(['collections', 'service'])->get();
        $grouped = $sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id);

        $this->collections = $grouped->map(function ($sales) {
            $s = $sales->first();
            $collected = $sales->sum(fn($x) => $x->collections->sum('amount'));
            $paid = $sales->sum('amount_paid');
            $total = $collected + $paid;

            return (object) [
                'beneficiary_name' => $s->beneficiary_name,
                'sale_date' => $s->sale_date,
                'service_label' => $s->service->label ?? '-', 
                'usd_sell' => $s->usd_sell,
                'amount_paid' => $paid,
                'collected' => $collected,
                'total_paid' => $total,
                'remaining' => $s->usd_sell - $total,
                'note' => $s->note,
                'scenarios' => $sales->map(function ($sale) {
                    return [
                        'date' => $sale->sale_date,
                        'usd_sell' => $sale->usd_sell,
                        'amount_paid' => $sale->amount_paid,
                        'status' => $sale->status,
                        'note' => $sale->reference ?? '-',
                    ];
                }),
                'collections' => $sales->flatMap->collections->map(function ($col) {
                    return [
                        'amount' => $col->amount,
                        'payment_date' => $col->payment_date,
                        'note' => $col->note,
                    ];
                }),
            ];
        })->values(); // ✅ هذا السطر ضروري
    }


    public function render()
    {
        return view('livewire.agency.account-history-details', [
            'customer' => $this->customer, // ✅ هذا هو المهم
            'collections' => $this->collections, // اختياري إذا كنت تريد تمريره
        ])->layout('layouts.agency');
    }

    public function showDetails($index)
    {
        $this->activeSale = $this->collections[$index];
    }

    public function closeModal()
    {
        $this->activeSale = null;
    }


}
