<?php

namespace App\Livewire\Agency\Quotations;


use App\Http\Controllers\Agency\QuotationController;
use Livewire\Component;

class ShowQuotation extends Component
{
    public $quotationNumber;
    public $quotationDate;
    public $toClient = 'Client / Company Name';
    public $taxName = 'Tax';
    public $taxRate = 15;
    public $services = [];
    public $terms = [
        'This price is subject to change unless booking and service issuance are confirmed.'
    ];
    public $notes = '';

    // 🔹 حالة اللغة داخل Livewire
    public $lang = 'en';

    public function mount()
    {
        $this->quotationDate = now()->format('Y-m-d');
        $last = cache()->increment('quotation_number', 1);
        $this->quotationNumber = 'ATHKA-Q-' . str_pad($last, 4, '0', STR_PAD_LEFT);

        // استعادة آخر لغة من الجلسة (افتراضيًا إنجليزي)
        $this->lang = session('quotation_lang', 'en');
    }

    // 🔹 تغيير اللغة وحفظها في الجلسة + إرسال حدث للـ JS
    public function setLang($lang)
    {
        $this->lang = in_array($lang, ['ar', 'en']) ? $lang : 'en';
        session(['quotation_lang' => $this->lang]);
        $this->dispatch('lang-changed', lang: $this->lang);
    }

    public function addServiceRow()
    {
        $this->services[] = [
            'airline'     => '',
            'details'     => '',
            'weight'      => '',
            'class'       => '',
            'conditions'  => '',
            'price'       => 0,
        ];
    }

    public function resetForm()
    {
        $this->toClient = 'Client / Company Name';
        $this->taxName = 'Tax';
        $this->taxRate = 15;
        $this->services = [];
        $this->terms = [
            'This price is subject to change unless booking and service issuance are confirmed.'
        ];
        $this->notes = '';
        $this->quotationDate = now()->format('Y-m-d');

        $last = cache()->increment('quotation_number', 1);
        $this->quotationNumber = 'ATHKA-Q-' . str_pad($last, 4, '0', STR_PAD_LEFT);
    }

    public function addTerm()
    {
        $this->terms[] = '';
    }

    public function getTotalProperty()
    {
        return collect($this->services)->sum('price');
    }

    public function getTaxAmountProperty()
    {
        return $this->total * ($this->taxRate / 100);
    }

    public function getGrandTotalProperty()
    {
        return $this->total + $this->taxAmount;
    }

    public function render()
    {
        return view('livewire.agency.quotations.show-quotation')->layout('layouts.agency');
    }
}
