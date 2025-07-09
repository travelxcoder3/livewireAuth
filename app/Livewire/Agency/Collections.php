<?php

namespace App\Livewire\Agency;

use App\Models\Sale;
use App\Models\DynamicListItemSub;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Collections extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;

    public $customerType = '';
    public $debtType = '';
    public $responseType = '';
    public $relationType = '';
    public $movementType = '';

    public function render()
    {
        $query = Sale::with(['customer', 'account', 'serviceType', 'provider', 'collections'])
            ->where('agency_id', Auth::user()->agency_id);

        if ($this->search) {
            $query->where('beneficiary_name', 'like', "%{$this->search}%");
        }

        if ($this->startDate) {
            $query->whereDate('sale_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('sale_date', '<=', $this->endDate);
        }

        $sales = $query->orderBy('sale_date', 'desc')->paginate(10);

        return view('livewire.agency.collections', [
            'sales' => $sales,
            'customerTypes' => $this->getOptions('نوع العميل'),
            'debtTypes' => $this->getOptions('نوع المديونية'),
            'responseTypes' => $this->getOptions('تجاوب العميل'),
            'relationTypes' => $this->getOptions('نوع ارتباطه بالشركة'),
        ])->layout('layouts.agency');
    }

    protected function getOptions($label)
    {
        return DynamicListItemSub::whereHas('parentItem', fn($q) =>
            $q->where('label', $label)
        )->get();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->customerType = '';
        $this->debtType = '';
        $this->responseType = '';
        $this->relationType = '';
        $this->movementType = '';
    }
}
