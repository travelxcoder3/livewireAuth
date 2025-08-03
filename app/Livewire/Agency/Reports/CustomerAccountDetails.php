<?php
namespace App\Livewire\Agency\Reports;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Collection;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class CustomerAccountDetails extends Component
{
    public $customerId;
    public $customer;
    public $sales = [];
    public $collections = [];

    public function mount($id)
    {
        $this->customerId = $id;

        $this->customer = Customer::where('agency_id', Auth::user()->agency_id)
            ->findOrFail($id);

        $this->sales = Sale::where('customer_id', $id)
            ->where('agency_id', Auth::user()->agency_id)
            ->get();

        $this->collections = Collection::whereHas('sale', fn($q) =>
            $q->where('customer_id', $id)
        )->where('agency_id', Auth::user()->agency_id)->get();
    }

    public function render()
    {
        return view('livewire.agency.reportsView.customer-account-details');
    }
}
