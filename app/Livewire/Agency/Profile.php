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
    public $logo; // الصورة الجديدة
    public $tempLogoUrl; // لمعاينة الشعار قبل الحفظ
    public $logoPreview; // لعرض الشعار بعد الحفظ مباشرة

    public function mount()
    {
        $this->agency = Auth::user()->agency;

        $this->fill([
            'phone' => $this->agency->phone,
            'landline' => $this->agency->landline,
            'email' => $this->agency->email,
            'address' => $this->agency->address,
            'description' => $this->agency->description,
        ]);

        $this->logoPreview = $this->agency->logo
            ? Storage::url($this->agency->logo) . '?v=' . now()->timestamp
            : asset('images/default-agency-logo.png');
    }

    public function updatedLogo()
    {
        $this->validate([
            'logo' => 'nullable|image|max:2048',
        ]);

        // رفع الشعار مباشرة عند اختياره
        if ($this->logo) {
            if ($this->agency->logo && Storage::disk('public')->exists($this->agency->logo)) {
                Storage::disk('public')->delete($this->agency->logo);
            }

            $logoPath = $this->logo->store('agencies/logos', 'public');
            $this->agency->logo = $logoPath;
            $this->agency->save();

            $this->logoPreview = Storage::url($logoPath) . '?v=' . now()->timestamp;
            $this->logo = null;
            $this->tempLogoUrl = null;

            session()->flash('success', 'تم رفع الشعار بنجاح!');
        }
    }

 public function update()
{
    $this->validate([
        'phone' => 'required|string|max:20',
        'landline' => 'nullable|string|max:20',
        'email' => 'required|email|unique:agencies,email,' . $this->agency->id,
        'address' => 'nullable|string',
        'description' => 'nullable|string',
        //'logo' => 'nullable|image|max:2048',
    ]);

    $this->agency->phone = $this->phone;
    $this->agency->landline = $this->landline;
    $this->agency->email = $this->email;
    $this->agency->address = $this->address;
    $this->agency->description = $this->description;

    if ($this->logo) {
        if ($this->agency->logo && Storage::disk('public')->exists($this->agency->logo)) {
            Storage::disk('public')->delete($this->agency->logo);
        }

        $logoPath = $this->logo->store('agencies/logos', 'public');
        $this->agency->logo = $logoPath;
    }

    $this->agency->save();

    $this->agency = Agency::find($this->agency->id);

    $this->logo = null;
    $this->tempLogoUrl = null;

    $this->logoPreview = $this->agency->logo
        ? Storage::url($this->agency->logo) . '?v=' . now()->timestamp
        : asset('images/default-agency-logo.png');

    session()->flash('success', 'تم تعديل البيانات بنجاح');
}


    public function render()
    {
        return view('livewire.agency.profile', [
            'logoPreview' => $this->logoPreview,
        ])->layout('layouts.agency');
    }
}
