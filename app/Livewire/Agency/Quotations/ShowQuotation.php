<?php

namespace App\Livewire\Agency\Quotations;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationTerm;
use App\Models\DynamicListItem;

class ShowQuotation extends Component
{
    public $quotationNumber, $quotationDate, $toClient='Client / Company Name';
    public $taxName='Tax', $taxRate=15, $services=[], $terms=['This price is subject to change unless booking and service issuance are confirmed.'];
    public $notes='', $lang='en';
    public $serviceOptions = []; 
    public $quotationId = null;
    public $availableTerms = [];
    public $termPicker = '';
    public $newTerm = '';

    public function getDisplayNumberProperty()
    {
        if ($this->quotationNumber) {
            return $this->quotationNumber; // بعد الحفظ
        }
        $nextId = (int)(Quotation::max('id') ?? 0) + 1; // للعرض فقط
        return 'ATHKA-Q-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
    }
    public function mount()
    {
        $this->quotationDate = now()->format('Y-m-d');
        $this->quotationNumber = null;
        $this->lang = session('quotation_lang', 'en');
        $agencyId = Auth::user()->agency_id;
        $this->serviceOptions = DynamicListItem::whereHas('list', function ($q) {
                $q->where('name', 'قائمة الخدمات');
            })
            ->where(function ($q) {
                $q->where('created_by_agency', auth()->user()->agency_id)
                ->orWhereNull('created_by_agency');
            })
            ->orderBy('label')
            ->pluck('label', 'id')  
            ->toArray();
        $this->availableTerms = DynamicListItem::whereHas('list', fn($q)=>$q->where('name','قائمة الشروط'))
        ->where(fn($q)=>$q->where('created_by_agency', auth()->user()->agency_id)->orWhereNull('created_by_agency'))
        ->orderBy('label')->pluck('label','id')->toArray();


    }
    public function setLang($lang)
    {
        $this->lang = in_array($lang, ['ar','en']) ? $lang : 'en';
        session(['quotation_lang'=>$this->lang]);
        $this->dispatch('lang-changed', lang:$this->lang);
    }

    public function downloadQuotationPdf(int $id): void
        {
            $this->dispatch('open-url', url: route('agency.quotations.pdf', $id));
        }

    public function openQuotationPrint(int $id): void
        {
            $this->dispatch('open-url', url: route('agency.quotations.view', $id));
        }
    public function addServiceRow()
    {
        $this->services[] = [
            'service_type_id' => '',
            'route'           => '',
            'date'            => now()->format('Y-m-d'),
            'description'     => '',
            'conditions'      => '',
            'price'           => 0,
        ];
    }
    public function addTerm()
    { 
        $this->terms[]=''; 
    }
    public function getTotalProperty()
    { 
        return collect($this->services)->sum(fn($s)=> (float)($s['price'] ?? 0));
    }
    public function getTaxAmountProperty()
    {
         return $this->total * ($this->taxRate/100);
    }
    public function getGrandTotalProperty()
    { 
        return $this->total + $this->taxAmount; 
    }
    public function save()
    {
      $this->validate([
            'quotationDate' => 'required|date',
            'taxRate' => 'numeric|min:0|max:100',
            'services' => 'array|min:1',
            'services.*.price' => 'numeric|min:0',
            'terms' => 'array',
            // إمّا بدون حد، أو حد كبير:
            // 'terms.*' => 'string',
            'terms.*' => 'string|max:5000',
        ]);


        $user = Auth::user();
        $agency = $user->agency;

             // فلترة الخدمات الفارغة
       $services = collect($this->services)->filter(function($s){
            return ($s['service_type_id'] ?? '') !== ''
                || trim((string)($s['route'] ?? '')) !== ''
                || trim((string)($s['description'] ?? '')) !== ''
                || (float)($s['price'] ?? 0) > 0;
        })->values();

       
        $terms = collect($this->terms)
            ->map(fn($v)=>(string)$v)->map(fn($v)=>trim($v))->filter()->unique()->values();

        $q = Quotation::create([
            'agency_id'       => $agency->id,
            'user_id'         => $user->id,
            'quotation_date'  => $this->quotationDate,
            'to_client'       => $this->toClient,
            'tax_name'        => $this->taxName,
            'tax_rate'        => (float)$this->taxRate,
            'currency'        => $agency->currency ?? 'USD',
            'lang'            => $this->lang,
            'notes'           => $this->notes,
            'subtotal'        => (float)$this->total,
            'tax_amount'      => (float)$this->taxAmount,
            'grand_total'     => (float)$this->grandTotal,
        ]);

   
      $q->terms()->createMany(
                        $terms->map(fn($t,$i)=>['value'=>$t,'position'=>$i])->all()
                    );

       
       foreach ($services as $i => $s) {
           $serviceName = $this->serviceOptions[$s['service_type_id'] ?? ''] ?? null;


            QuotationItem::create([
                'quotation_id' => $q->id,
                'position'     => $i,
                'service_name'      => $serviceName,                 
                'description'      => $s['description'] ?? null, 
                'route'       => $s['route'] ?? null,       
                'service_date'        => $s['date'] ?? null,           
                'conditions'   => $s['conditions'] ?? null,
                'price'        => (float)($s['price'] ?? 0),
            ]);
        }

            foreach ($terms as $i => $t) {
                QuotationTerm::create([
                    'quotation_id' => $q->id,
                    'position'     => $i,
                    'value'        => $t,
                ]);
            }

        $this->quotationId = $q->id; 
        $this->dispatch('notify', message: 'تم حفظ عرض السعر');
        $this->quotationNumber = $q->quotation_number;

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
        $this->quotationId = null;
        $this->quotationNumber = null;
    }
    public function addPickedTerm()
    {
        if($this->termPicker && isset($this->availableTerms[$this->termPicker])){
            $this->terms[] = $this->availableTerms[$this->termPicker];
            $this->termPicker = '';
        }
    }
    public function addCustomTerm()
    {
        $v = trim((string)$this->newTerm);
        if($v !== ''){ $this->terms[] = $v; $this->newTerm = ''; }
    }
    public function removeTerm($i)
    {
        unset($this->terms[$i]);
        $this->terms = array_values($this->terms);
    }
    public function render()
    {
        return view('livewire.agency.quotations.show-quotation')->layout('layouts.agency');
    }
}
