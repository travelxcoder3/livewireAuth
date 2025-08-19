<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class AddCustomer extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $images = [];
    public $deletedImageIds = [];
    public $showWallet = false;
    public $walletCustomerId = null;
    // AddCustomer.php
protected $listeners = ['wallet-closed' => 'closeWallet'];


    public function deleteExistingImage($id)
    {
        $this->deletedImageIds[] = $id;
    }

    

    public $name, $email, $phone, $address, $has_commission = false;

    public $showModal = false;
    public $search = '';
    public $phoneFilter = '';
    public $addressFilter = '';
    public $commissionFilter = '';
    public $accountTypeFilter = ''; // فلتر نوع الحساب
    public $account_type = 'individual'; // القيمة الابتدائية
    public $existingImages = []; // الصور القديمة (من قاعدة البيانات)

    public function openModal()
{
    $this->resetFields();
    $this->account_type = 'individual'; // ✅ فقط هنا إذا كنا في وضع إضافة
    $this->editingId = null;
    $this->showModal = true;
}


    public function closeModal()
    {
        $this->reset([
    'name', 'email', 'phone', 'address', 'has_commission', 'account_type',
    'images', 'existingImages', 'editingId', 'deletedImageIds'
]);

        $this->account_type = 'individual'; // إعادة القيمة الافتراضية
        $this->showModal = false;
    }


    public function render()
    {
        \Log::info('Commission Filter Value:', ['value' => $this->commissionFilter]);

        $customers = Customer::with('images')
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
            ->when($this->accountTypeFilter, function ($query) {
                $query->where('account_type', $this->accountTypeFilter);
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
        $this->account_type = $customer->account_type;
        $this->existingImages = $customer->images->pluck('image_path', 'id')->toArray();
        $this->showModal = true; // ✅ فتح المودال عند التعديل
    }


    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'account_type' => 'required|in:individual,company,organization',
            'images.*' => 'nullable|image|max:2048',
        ]);

        if ($this->editingId) {
    $customer = Customer::findOrFail($this->editingId);
    $customer->update([
        'name' => $this->name,
        'email' => $this->email,
        'phone' => $this->phone,
        'address' => $this->address,
        'has_commission' => $this->has_commission,
        'account_type' => $this->account_type,
    ]);

if (!empty($this->deletedImageIds)) {
    foreach ($this->deletedImageIds as $id) {
        $image = \App\Models\CustomerImage::find($id);
        if ($image && $image->customer_id == $customer->id) {
            \Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }
    }


}


    // حفظ الصور الجديدة
   foreach ($this->images as $image) {
    if ($image instanceof \Illuminate\Http\UploadedFile) {
        $path = $image->store('customers', 'public');
        $customer->images()->create([
            'image_path' => $path,
        ]);
    }
}



$this->existingImages = $customer->images->pluck('image_path', 'id')->toArray();

    session()->flash('message', 'تم تحديث بيانات العميل بنجاح');
    session()->flash('type', 'success');
}
 else {
            // إضافة جديد
        $customer = Customer::create([
                'agency_id' => auth()->user()->agency_id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'has_commission' => $this->has_commission,
                'account_type' => $this->account_type,
            ]);

            if ($this->images && is_array($this->images)) {
                foreach ($this->images as $image) {
                    $path = $image->store('customers', 'public');
                    $customer->images()->create([
                        'image_path' => $path,
                    ]);
                }
            }


            session()->flash('message', 'تم إضافة العميل بنجاح');
            session()->flash('type', 'success');

        }

       $this->reset([
    'name', 'email', 'phone', 'address', 'has_commission', 'account_type',
    'images', 'existingImages', 'editingId', 'deletedImageIds'
]);
        $this->showModal = false;
    }
    public function resetFilters()
    {
        $this->reset([
    'name', 'email', 'phone', 'address', 'has_commission', 'account_type',
    'images', 'existingImages', 'editingId', 'deletedImageIds'
]);
    }


    public function resetFields()
    {
        $this->reset([
            // حقول النموذج
            'name', 'email', 'phone', 'address', 'editingId', 'has_commission',
            
            // حقول الفلاتر
            'search', 'phoneFilter', 'addressFilter', 'commissionFilter', 'accountTypeFilter',
        ]);
    }


public function addImage()
{
    $this->images[] = null;
}

public function removeImage($index)
{
    unset($this->images[$index]);
    $this->images = array_values($this->images); // إعادة الفهرسة
}


public function openWallet($id)
{
    $this->walletCustomerId = (int) $id;
    $this->showWallet = true;
}

public function closeWallet()
{
    $this->showWallet = false;
    $this->walletCustomerId = null;
}


}


