<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;

class AllCollections extends Component
{
    use WithPagination;

    public $search = '';
    public $sales = []; // تغيير الاسم ليكون أكثر شمولاً
    public $activeSaleId = null;

    public function mount()
    {
        $this->loadSales();
    }

  

    public function getPaymentStatus($sale)
    {
        $totalPaid = $sale->collections->sum('amount');
        
        if ($totalPaid == 0) {
            return [
                'status' => 'لم يبدأ التحصيل',
                'color' => 'bg-gray-100 text-gray-800'
            ];
        } elseif ($totalPaid < $sale->usd_sell) {
            return [
                'status' => 'تحصيل جزئي',
                'color' => 'bg-amber-100 text-amber-800'
            ];
        } else {
            return [
                'status' => 'تم التحصيل بالكامل',
                'color' => 'bg-green-100 text-green-800'
            ];
        }
    }

    public function showCollectionDetails($saleId)
    {
        $this->activeSaleId = $saleId;
    }

    public function closeModal()
    {
        $this->activeSaleId = null;
    }

    public function render()
    {
        return view('livewire.agency.all-collections')
            ->layout('layouts.agency');
    }

   

public function loadSales()
{
    $query = Sale::with('collections')
        ->where('agency_id', auth()->user()->agency_id)
        ->where(function ($q) {
            $q->where('payment_method', '!=', 'all')
              ->orWhere(function ($q2) {
                  $q2->where('payment_method', 'all')
                     ->whereHas('collections', function ($q3) {
                         $q3->selectRaw('SUM(amount) as total')->groupBy('sale_id')
                             ->havingRaw('SUM(amount) < sales.usd_sell');
                     })
                     ->orWhereDoesntHave('collections');
              });
        })
        // ✅ استثناء العمليات الكاش المدفوعة بالكامل
        ->where(function ($q) {
            $q->where('payment_method', '!=', 'kash')
              ->orWhereHas('collections', function ($q2) {
                  $q2->selectRaw('SUM(amount) as total')->groupBy('sale_id')
                      ->havingRaw('SUM(amount) < sales.usd_sell');
              });
        })
        ->latest();

    if (!empty($this->search)) {
        $query->where(function($q) {
            $q->where('beneficiary_name', 'like', '%'.$this->search.'%')
              ->orWhere('usd_sell', 'like', '%'.$this->search.'%')
              ->orWhere('id', 'like', '%'.$this->search.'%');
        });
    }

    $this->sales = $query->get();
}


    public function updatedSearch()
    {
        $this->loadSales();
    }

    // ... باقي الدوال الموجودة سابقاً ...
}