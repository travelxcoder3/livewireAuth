<?php

namespace App\Livewire\Agency\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class SaleEditWindow extends Component
{
    public int $hours = 72;
    public ?string $successMessage = null;

    public function mount(): void
    {
        $this->hours = (int) (Auth::user()->agency->sale_edit_hours ?? config('agency.defaults.sale_edit_hours', 72));
    }

    protected function rules(): array
    {
        return ['hours' => 'required|integer|min:0|max:720'];
    }

    public function save(): void
    {
        $this->validate();
        Auth::user()->agency->update(['sale_edit_hours' => $this->hours]);
        $this->successMessage = 'تم حفظ مهلة التعديل.';
    }

    public function render()
    {
        return view('livewire.agency.settings.sale-edit-window');
    }
}
