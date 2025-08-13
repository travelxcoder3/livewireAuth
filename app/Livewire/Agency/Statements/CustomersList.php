<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class CustomersList extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true, keep: true)]
    public string $search = '';

    public function updatingSearch() { $this->resetPage(); }

    private function normalize(string $s): string
    {
        $s = str_replace('ـ','', trim($s));
        $map = ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''];
        return mb_strtolower(strtr($s, $map));
    }

    public function render()
    {
        $term = $this->normalize($this->search);
        $customers = Customer::where('agency_id', Auth::user()->agency_id)
            ->when($term !== '', function($q) use($term){
                $sql = "LOWER(name)";
                foreach (['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ئ'=>'ي','ة'=>'ه','ؤ'=>'و','ء'=>''] as $f=>$t) {
                    $sql = "REPLACE($sql,'$f','$t')";
                }
                $q->whereRaw("$sql LIKE ?", ['%'.$term.'%']);
            })
            ->orderByDesc('id')->paginate(10);

        return view('livewire.agency.statements.customers-list', compact('customers'));
    }
}
