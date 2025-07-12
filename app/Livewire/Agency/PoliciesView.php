<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\AgencyPolicy;
use Illuminate\Support\Facades\Auth;

class PoliciesView extends Component
{
    public function render()
    {
        $policies = AgencyPolicy::where('agency_id', Auth::user()->agency_id)->get();
        
        return view('livewire.agency.policies-view', compact('policies'))
            ->layout('layouts.agency')
            ->title('عرض السياسات - ' . Auth::user()->agency->name);
    }
}