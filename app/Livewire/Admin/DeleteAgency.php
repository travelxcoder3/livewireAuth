<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Agency;

class DeleteAgency extends Component
{
    public $agencyId;
    public $agencyName;

    public function mount(Agency $agency)
    {
        $this->agencyId = $agency->id;
        $this->agencyName = $agency->name;
    }

    public function confirmDelete()
    {
        $agency = Agency::findOrFail($this->agencyId);
        // حذف جميع المستخدمين المرتبطين بالوكالة
        foreach ($agency->users as $user) {
            $user->delete();
        }
        $agency->delete();
        return redirect()->route('admin.agencies')->with('message', 'تم حذف الوكالة وجميع المستخدمين المرتبطين بها بنجاح');
    }

    public function cancelDelete()
    {
        return redirect()->route('admin.agencies')->with('message', 'تم إلغاء عملية الحذف.');
    }

    public function render()
    {
        return view('livewire.admin.delete-agency')->layout('layouts.admin');
    }
} 