<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\AgencyPolicy;
use Illuminate\Support\Facades\Auth;

class AgencyPolicies extends Component
{
    public $policies;
    public $title, $content;
    public $editingPolicy = null;
    public $showModal = false;
    public $showDeleteModal = false;
    public $isEdit = false;
    public $policyToDelete = null;

    /** الإعداد الخاص بنافذة تعديل المبيعات (بالدقائق) */
    public int $salesEditWindowMinutes = 180;

    public function mount()
    {
        $this->loadPolicies();
    }

    public function loadPolicies()
    {
        $agency = Auth::user()->agency;

        // قائمة السياسات النصية كالمعتاد
        $this->policies = $agency->policies()->latest()->get();

        // قراءة قيمة مدة السماح من سياسة بمفتاح sales_edit_window_minutes
        $this->salesEditWindowMinutes = (int) (
            $agency->policies()
                ->where('key', 'sales_edit_window_minutes')
                ->value('content') ?? 180
        );
    }

    public function create()
    {
        $this->resetFields();
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $policy = AgencyPolicy::findOrFail($id);
        $this->editingPolicy = $policy;
        $this->title = $policy->title;
        $this->content = $policy->content;
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function confirmDelete($id)
    {
        $this->policyToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        AgencyPolicy::findOrFail($this->policyToDelete)->delete();
        $this->showDeleteModal = false;
        $this->loadPolicies();
        session()->flash('message', 'تم حذف السياسة بنجاح');
        session()->flash('type', 'success');
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($this->isEdit && $this->editingPolicy) {
            $this->editingPolicy->update([
                'title'   => $this->title,
                'content' => $this->content,
            ]);
            session()->flash('message', 'تم تحديث السياسة بنجاح');
            session()->flash('type', 'success');
        } else {
            Auth::user()->agency->policies()->create([
                'title'     => $this->title,
                'content'   => $this->content,
                // لو لديك عمود key وتريد سياسة نصية عامة اتركه null
                'key'       => null,
            ]);
            session()->flash('message', 'تم إضافة السياسة بنجاح');
            session()->flash('type', 'success');
        }

        $this->resetFields();
        $this->loadPolicies();
        $this->showModal = false;
    }

    /** حفظ قيمة مدة السماح لتعديل المبيعات بالدقائق */
    public function saveSalesEditWindow()
    {
        $this->validate([
            'salesEditWindowMinutes' => 'required|integer|min:0|max:14400', // حتى 10 أيام
        ]);

        $agency = Auth::user()->agency;

        $agency->policies()->updateOrCreate(
            ['key' => 'sales_edit_window_minutes'],
            [
                'title'   => 'مدة السماح لتعديل المبيعات (بالدقائق)',
                'content' => (string) $this->salesEditWindowMinutes,
            ]
        );

        session()->flash('message', 'تم حفظ مدة السماح لتعديل المبيعات.');
        session()->flash('type', 'success');

        $this->loadPolicies();
    }

    public function resetFields()
    {
        $this->title = '';
        $this->content = '';
        $this->editingPolicy = null;
        $this->policyToDelete = null;
    }

    public function render()
    {
        return view('livewire.agency.agency-policies')
            ->layout('layouts.agency')
            ->title('سياسات الوكالة - ' . Auth::user()->agency->name);
    }
}
