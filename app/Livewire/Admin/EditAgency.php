<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Agency;

class EditAgency extends Component
{
    public $agencyId;
    public $agency_name;
    public $agency_email;
    public $agency_phone;
    public $agency_address;
    public $license_number;
    public $commercial_record;
    public $tax_number;
    public $license_expiry_date;
    public $description;
    public $max_users;
    public $successMessage;

    public function mount(Agency $agency)
    {
        $this->agencyId = $agency->id;
        $this->agency_name = $agency->name;
        $this->agency_email = $agency->email;
        $this->agency_phone = $agency->phone;
        $this->agency_address = $agency->address;
        $this->license_number = $agency->license_number;
        $this->commercial_record = $agency->commercial_record;
        $this->tax_number = $agency->tax_number;
        $this->license_expiry_date = $agency->license_expiry_date;
        $this->description = $agency->description;
        $this->max_users = $agency->max_users;
    }

    public function updateAgency()
    {
        $this->validate([
            'agency_name' => 'required|string|max:255',
            'agency_email' => 'required|email',
            'agency_phone' => 'required|string|max:30',
            'agency_address' => 'required|string|max:255',
            'license_number' => 'required|string',
            'commercial_record' => 'required|string',
            'tax_number' => 'required|string',
            'license_expiry_date' => 'required|date',
            'max_users' => 'required|integer|min:1',
        ]);
        $agency = Agency::findOrFail($this->agencyId);
        $agency->update([
            'name' => $this->agency_name,
            'email' => $this->agency_email,
            'phone' => $this->agency_phone,
            'address' => $this->agency_address,
            'license_number' => $this->license_number,
            'commercial_record' => $this->commercial_record,
            'tax_number' => $this->tax_number,
            'license_expiry_date' => $this->license_expiry_date,
            'description' => $this->description,
            'max_users' => $this->max_users,
        ]);
        $this->successMessage = 'تم تحديث بيانات الوكالة بنجاح';
        return redirect()->route('admin.agencies')->with('message', 'تم تحديث بيانات الوكالة بنجاح');
    }

    public function render()
    {
        return view('livewire.admin.edit-agency')->layout('layouts.admin');
    }
} 