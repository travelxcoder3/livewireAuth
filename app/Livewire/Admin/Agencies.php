<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Agency;
use Livewire\WithPagination;

class Agencies extends Component
{
    use WithPagination;

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
    public $landline;
    public $currency;
    public $status;


    public $successMessage;
    public $perPage = 10; // عدد الصفوف لكل صفحة
    public $showAll = false; // عرض كل البيانات
    public $search = '';

    // متغيرات مودال تغيير كلمة المرور
    public $showPasswordModal = false;
    public $selectedAgencyId = null;
    public $newPassword = '';
    public $confirmPassword = '';


    public function render()
    {
        $query = Agency::with('admin')
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $agencies = $query->paginate($this->perPage);  // استخدام الترقيم

        return view('livewire.admin.agencies', compact('agencies'))
            ->layout('layouts.admin');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function toggleShowAll()
    {
        $this->showAll = !$this->showAll;
        $this->resetPage();
    }

    // public function render()
    // {
    //     $agencies = Agency::with('admin')->get();
    //     return view('livewire.admin.agencies', compact('agencies'))
    //         ->layout('layouts.admin');
    // }

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
        $this->landline = $agency->landline;
        $this->currency = $agency->currency;
        $this->status = $agency->status;
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
            'landline' => 'nullable|string|max:30',
            'currency' => 'required|string|max:10',
            'status' => 'required|string',
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
            'landline' => $this->landline,
            'currency' => $this->currency,
            'status' => $this->status,
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

    // دالة لعرض المودال
    public function showPasswordModal()
    {
        $this->showPasswordModal = true;
    }
    // دالة لتحديث كلمة المرور
    public function updatePassword()
    {
        $this->validate([
            'selectedAgencyId' => 'required|exists:agencies,id',
            'newPassword' => 'required|min:8',
            'confirmPassword' => 'required|same:newPassword',
        ]);

        try {
            // احصل على الوكالة مع مديرها
            $agency = Agency::with('admin')->findOrFail($this->selectedAgencyId);

            if (!$agency->admin) {
                throw new \Exception('لا يوجد مدير مرتبط بهذه الوكالة');
            }

            // تحديث كلمة مرور مدير الوكالة
            $agency->admin->update([
                'password' => bcrypt($this->newPassword)
            ]);

            // إغلاق المودال وإظهار رسالة النجاح
            $this->reset(['showPasswordModal', 'selectedAgencyId', 'newPassword', 'confirmPassword']);
            session()->flash('message', 'تم تحديث كلمة مرور مدير الوكالة بنجاح');

        } catch (\Exception $e) {
            $this->reset(['newPassword', 'confirmPassword']);
            session()->flash('error', $e->getMessage());
        }
    }
}
