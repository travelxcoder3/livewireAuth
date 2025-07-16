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
    public $landline;
    public $currency;
    public $status;
    public $agency_address;
    public $license_number;
    public $commercial_record;
    public $tax_number;
    public $license_expiry_date;
    public $subscription_start_date;
    public $subscription_end_date;
    public $description;
    public $max_users;
    public $successMessage;
    public $parent_agency_id;
    public $agenciesList = [];

    public function mount(Agency $agency)
    {
        $this->agencyId = $agency->id;
        $this->agency_name = $agency->name;
        $this->agency_email = $agency->email;
        $this->agency_phone = $agency->phone;
        $this->landline = $agency->landline;
        $this->currency = $agency->currency;
        $this->status = $agency->status;
        $this->agency_address = $agency->address;
        $this->license_number = $agency->license_number;
        $this->commercial_record = $agency->commercial_record;
        $this->tax_number = $agency->tax_number;
        $this->license_expiry_date = $agency->license_expiry_date ? (is_string($agency->license_expiry_date) ? $agency->license_expiry_date : $agency->license_expiry_date->format('Y-m-d')) : '';
        $this->subscription_start_date = $agency->subscription_start_date ? (is_string($agency->subscription_start_date) ? $agency->subscription_start_date : $agency->subscription_start_date->format('Y-m-d')) : '';
        $this->subscription_end_date = $agency->subscription_end_date ? (is_string($agency->subscription_end_date) ? $agency->subscription_end_date : $agency->subscription_end_date->format('Y-m-d')) : '';
        $this->description = $agency->description;
        $this->max_users = $agency->max_users ?? 1;
        $this->parent_agency_id = $agency->parent_agency_id;
        $this->agenciesList = Agency::where('id', '!=', $agency->id)->pluck('name', 'id')->toArray();
    }

    public function updateAgency()
    {
        $this->validate([
            'agency_name' => 'required|string|max:255',
            'agency_email' => 'required|email',
            'agency_phone' => 'required|string|max:30',
            'landline' => 'nullable|string|max:30',
            'currency' => 'required|string|max:10',
            'status' => 'required|string',
            'agency_address' => 'required|string|max:255',
            'license_number' => 'required|string',
            'commercial_record' => 'required|string',
            'tax_number' => 'required|string',
            'license_expiry_date' => 'required|date',
            'subscription_start_date' => 'required|date|before_or_equal:subscription_end_date',
            'subscription_end_date' => 'required|date|after_or_equal:subscription_start_date',
            'max_users' => 'required|integer|min:1',
            'parent_agency_id' => 'nullable|exists:agencies,id',
        ]);
        $agency = Agency::findOrFail($this->agencyId);
        $agency->update([
            'name' => $this->agency_name,
            'email' => $this->agency_email,
            'phone' => $this->agency_phone,
            'landline' => $this->landline,
            'currency' => $this->currency,
            'status' => $this->status,
            'address' => $this->agency_address,
            'license_number' => $this->license_number,
            'commercial_record' => $this->commercial_record,
            'tax_number' => $this->tax_number,
            'license_expiry_date' => $this->license_expiry_date,
            'subscription_start_date' => $this->subscription_start_date,
            'subscription_end_date' => $this->subscription_end_date,
            'description' => $this->description,
            'max_users' => $this->max_users,
            'parent_agency_id' => $this->parent_agency_id,
        ]);
        $this->successMessage = 'تم تحديث بيانات الوكالة بنجاح';
        return redirect()->route('admin.agencies')->with('message', 'تم تحديث بيانات الوكالة بنجاح');
    }

    public function render()
    {
        return view('livewire.admin.edit-agency', [
            'agenciesList' => $this->agenciesList,
        ])->layout('layouts.admin');
    }
} 