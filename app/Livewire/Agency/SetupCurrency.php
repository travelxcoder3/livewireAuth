<?php
namespace App\Livewire\Agency;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SetupCurrency extends Component
{
    public string $currency = '';
    public array $currencies = ['USD', 'EUR', 'SAR', 'YER'];

    public function save()
    {
        $agency = Auth::user()->agency;

        if ($agency->currency) {
            return redirect()->route('agency.dashboard');
        }

        $this->validate([
            'currency' => 'required|in:' . implode(',', $this->currencies),
        ]);

        $agency->currency = $this->currency;
        $agency->save();

        return redirect()->route('agency.dashboard')->with('success', 'تم حفظ العملة بنجاح.');
    }

    public function render()
    {
        return view('livewire.agency.setup-currency')
            ->layout('layouts.agency');

    }
}
