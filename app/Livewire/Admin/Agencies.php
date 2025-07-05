<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Agency;

class Agencies extends Component
{
    // متغيرات المودال
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $editAgencyId;
    public $deleteAgencyId;

    // متغيرات النموذج
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

    public function render()
    {
        $agencies = Agency::with('admin')->get();
        return view('livewire.admin.agencies', compact('agencies'))
            ->layout('layouts.admin');
    }

    public function showEditModal($id)
    {
        $agency = Agency::findOrFail($id);
        $this->editAgencyId = $agency->id;
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
        $this->showEditModal = true;
    }

    public function saveEdit()
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
        $agency = Agency::findOrFail($this->editAgencyId);
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
        $this->showEditModal = false;
        $this->successMessage = 'تم تحديث بيانات الوكالة بنجاح';
    }

    public function showDeleteModal($id)
    {
        $this->deleteAgencyId = $id;
        $this->showDeleteModal = true;
    }

    public function confirmDelete()
    {
        $agency = Agency::findOrFail($this->deleteAgencyId);
        $agency->delete();
        $this->showDeleteModal = false;
        session()->flash('message', 'تم حذف الوكالة بنجاح');
        return redirect()->route('admin.agencies');
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        session()->flash('message', 'تم إلغاء عملية الحذف.');
        return redirect()->route('admin.agencies');
    }
}
