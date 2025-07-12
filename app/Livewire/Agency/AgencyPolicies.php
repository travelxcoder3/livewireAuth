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
    public $showDeleteModal = false; // أضفنا هذا المتغير
    public $isEdit = false;
    public $policyToDelete = null; // أضفنا هذا المتغير لتخزين السياسة المحددة للحذف

    public function mount()
    {
        $this->loadPolicies();
    }

    public function loadPolicies()
    {
        $this->policies = Auth::user()->agency->policies()->latest()->get();
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
        session()->flash('success', 'تم حذف السياسة بنجاح');
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        if ($this->isEdit && $this->editingPolicy) {
            $this->editingPolicy->update([
                'title' => $this->title,
                'content' => $this->content,
            ]);
            session()->flash('success', 'تم تحديث السياسة بنجاح');
        } else {
            Auth::user()->agency->policies()->create([
                'title' => $this->title,
                'content' => $this->content,
            ]);
            session()->flash('success', 'تم إضافة السياسة بنجاح');
        }

        $this->resetFields();
        $this->loadPolicies();
        $this->showModal = false;
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