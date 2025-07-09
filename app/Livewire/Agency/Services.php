<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\ServiceType;
use Illuminate\Support\Facades\Auth;

class ServiceTypes extends Component
{
    public $serviceTypes;
    public $showModal = false;
    public $editMode = false;
    public $serviceTypeId;
    public $name;
    public $is_active = 1;

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->fetchServiceTypes();
    }

    public function fetchServiceTypes()
    {
        $this->serviceTypes = ServiceType::where('agency_id', Auth::user()->agency_id)->get();
    }

    public function showAddModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function showEditModal($id)
    {
        $type = ServiceType::findOrFail($id);
        $this->serviceTypeId = $type->id;
        $this->name = $type->name;
        $this->is_active = $type->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function saveServiceType()
    {
        $this->validate();

        if ($this->editMode) {
            $type = ServiceType::findOrFail($this->serviceTypeId);
            $type->update([
                'name' => $this->name,
                'is_active' => $this->is_active,
            ]);
            session()->flash('message', 'تم تحديث نوع الخدمة بنجاح');
        } else {
            ServiceType::create([
                'name' => $this->name,
                'is_active' => $this->is_active,
                'agency_id' => Auth::user()->agency_id,
            ]);
            session()->flash('message', 'تمت إضافة نوع الخدمة بنجاح');
        }

        $this->showModal = false;
        $this->fetchServiceTypes();
    }

    public function deleteServiceType($id)
    {
        $type = ServiceType::findOrFail($id);
        $type->delete();
        session()->flash('message', 'تم حذف نوع الخدمة بنجاح');
        $this->fetchServiceTypes();
    }

    public function resetForm()
    {
        $this->serviceTypeId = null;
        $this->name = '';
        $this->is_active = 1;
    }

    public function render()
    {
        return view('livewire.agency.service-types')
            ->layout('layouts.agency')
            ->title('أنواع الخدمات');
    }
}
