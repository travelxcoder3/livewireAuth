<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class AddCustomer extends Component
{
    use WithPagination;

    public $name, $email, $phone, $address;

  
    public function render()
    {
        $customers = Customer::where('agency_id', auth()->user()->agency_id)
            ->latest()
            ->paginate(10);

        return view('livewire.agency.add-customer', compact('customers'))
            ->layout('layouts.agency');
    }

    public $editingId = null;

public function edit($id)
{
    $customer = Customer::findOrFail($id);
    $this->editingId = $customer->id;
    $this->name = $customer->name;
    $this->email = $customer->email;
    $this->phone = $customer->phone;
    $this->address = $customer->address;
}

public function save()
{
    $this->validate([
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:255',
    ]);

    if ($this->editingId) {
        // تحديث العميل
        $customer = Customer::findOrFail($this->editingId);
        $customer->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ]);
        session()->flash('success', 'تم تحديث بيانات العميل بنجاح');
    } else {
        // إضافة جديد
        Customer::create([
            'agency_id' => auth()->user()->agency_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ]);
        session()->flash('success', 'تم إضافة العميل بنجاح');
    }

    $this->reset(['name', 'email', 'phone', 'address', 'editingId']);
}

public function resetFields()
{
    $this->reset(['name', 'email', 'phone', 'address', 'editingId']);
}

}


