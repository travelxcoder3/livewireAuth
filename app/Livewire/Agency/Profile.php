<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Agency;

class Profile extends Component
{
    use WithFileUploads;

    public $agency;
    public $phone;
    public $landline;
    public $email;
    public $address;
    public $description;
    public $logo; // للصورة الجديدة
    public $tempLogoUrl; // لعرض الصورة المؤقتة

    public function mount()
    {
        $this->agency = Auth::user()->agency;
        $this->fill([
            'phone' => $this->agency->phone,
            'landline' => $this->agency->landline,
            'email' => $this->agency->email,
            'address' => $this->agency->address,
            'description' => $this->agency->description
        ]);
    }

    public function updatedLogo()
    {
        $this->validate([
            'logo' => 'nullable|image|max:2048', // 2MB كحد أقصى
        ]);
        
        $this->tempLogoUrl = $this->logo->temporaryUrl();
    }

    public function update()
    {
        $validated = $this->validate([
            'phone' => 'required|string|max:20',
            'landline' => 'nullable|string|max:20',
            'email' => 'required|email|unique:agencies,email,'.$this->agency->id,
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);

        // تحديث الصورة إذا تم رفع جديدة
        if ($this->logo) {
            // حذف الصورة القديمة إذا كانت موجودة
            if ($this->agency->logo) {
                Storage::disk('public')->delete($this->agency->logo);
            }
            
            $validated['logo'] = $this->logo->store('agencies/logos', 'public');
        }

        $this->agency->update($validated);
        
        // إعادة تعيين المتغيرات بعد التحديث
        if ($this->logo) {
            $this->logo = null;
            $this->tempLogoUrl = null;
        }
        
        session()->flash('success', 'تم تعديل البيانات بنجاح');
    }

    public function render()
    {
        return view('livewire.agency.profile', [
            'currentLogo' => $this->agency->logo 
                ? Storage::url($this->agency->logo) 
                : asset('images/default-agency-logo.png')
        ])->layout('layouts.agency');
    }
}