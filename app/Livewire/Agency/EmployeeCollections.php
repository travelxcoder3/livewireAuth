<?php

namespace App\Livewire\Agency;

use Livewire\Component;

class EmployeeCollections extends Component
{
    public function render()
    {
        return view('livewire.agency.employee-collections')
            ->layout('layouts.agency')
            ->title('تحصيلات الموظفين');
    }
}
