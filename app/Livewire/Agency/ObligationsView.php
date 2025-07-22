<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\User;

class ObligationsView  extends Component
{
    public User $employee;

    public function mount()
    {
        $this->employee = auth()->user()->load('obligations');
    }

    public function render()
    {
        return view('livewire.agency.obligations-view', [
            'employee' => $this->employee,
        ])->layout('layouts.agency');
    }
}
