<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;

class Providers extends Component
{
    public $providers;
    public $showModal = false;
    public $editMode = false;
    public $contact_info;

    public $providerId;
    public $name;
    public $type;

    protected $rules = [
        'name' => 'required|string|max:255',
        'type' => 'nullable|string|max:255',
        'contact_info' => 'nullable|string',
    ];

    public function mount()
    {
        $this->fetchProviders();
    }

    public function fetchProviders()
    {
        $this->providers = Provider::where('agency_id', Auth::user()->agency_id)->latest()->get();
    }

    public function showAddModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function showEditModal($id)
    {
        $provider = Provider::findOrFail($id);
        $this->providerId = $provider->id;
        $this->name = $provider->name;
        $this->type = $provider->type;
        $this->contact_info = $provider->contact_info;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function saveProvider()
    {
        $this->validate();

        if ($this->editMode) {
            Provider::findOrFail($this->providerId)->update([
    'name' => $this->name,
    'type' => $this->type,
    'contact_info' => $this->contact_info,
]);
            session()->flash('message', 'تم تحديث المزود بنجاح.');
        } else {
           Provider::create([
    'agency_id' => Auth::user()->agency_id,
    'name' => $this->name,
    'type' => $this->type,
    'contact_info' => $this->contact_info,
]);

            session()->flash('message', 'تمت إضافة المزود بنجاح.');
        }

        $this->showModal = false;
        $this->fetchProviders();
    }

   

 public function resetForm()
{
    $this->providerId = null;
    $this->name = '';
    $this->type = '';
    $this->contact_info = '';
}

    public function render()
    {
        return view('livewire.agency.providers')
            ->layout('layouts.agency')
            ->title('إدارة المزودين');
    }
}
