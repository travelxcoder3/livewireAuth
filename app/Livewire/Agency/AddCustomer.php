<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class AddCustomer extends Component
{
    use WithPagination;

    public $name, $email, $phone, $address, $has_commission = false;

    public $showModal = false;
    public $search = '';
    public $phoneFilter = '';
    public $addressFilter = '';
    public $commissionFilter = '';
    public function openModal()
    {
        $this->resetFields(); // تنظيف الحقول
        $this->editingId = null; // إضافة جديدة
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function render()
    {
        \Log::info('Commission Filter Value:', ['value' => $this->commissionFilter]);

        $customers = Customer::query()
            ->where('agency_id', auth()->user()->agency_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->phoneFilter, function ($query) {
                $query->where('phone', 'like', '%' . $this->phoneFilter . '%');
            })
            ->when($this->addressFilter, function ($query) {
                $query->where('address', 'like', '%' . $this->addressFilter . '%');
            })
            ->when(!is_null($this->commissionFilter) && $this->commissionFilter !== '', function ($query) {
                $query->where('has_commission', (int) $this->commissionFilter);
            })
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
        $this->has_commission = (bool) $customer->has_commission;
        $this->showModal = true; // ✅ فتح المودال عند التعديل
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
                'has_commission' => $this->has_commission,
            ]);
            session()->flash('message', 'تم تحديث بيانات العميل بنجاح');
            session()->flash('type', 'success');

        } else {
            // إضافة جديد
            Customer::create([
                'agency_id' => auth()->user()->agency_id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'has_commission' => $this->has_commission,
            ]);
            session()->flash('message', 'تم إضافة العميل بنجاح');
            session()->flash('type', 'success');

        }

        $this->reset(['name', 'email', 'phone', 'address', 'editingId', 'has_commission']);
        $this->showModal = false;
    }
    public function resetFilters()
    {
        $this->reset(['search', 'phoneFilter', 'addressFilter', 'commissionFilter']);
    }

    public function resetFields()
    {
        $this->reset(['name', 'email', 'phone', 'address', 'editingId', 'has_commission']);
    }

}


