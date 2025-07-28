<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Agency;
use App\Models\AgencyTarget;
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
    public $monthlyTarget;
    public $editing = false; // false يعني أن الوضع الافتراضي هو عرض البيانات فقط
    public $currentTarget;

    public function startEditing()
    {
        $this->editing = true;
        // يمكنك هنا تعبئة بيانات النموذج إذا لزم الأمر
    }

    public function cancelEditing()
    {
        $this->editing = false;
        $this->resetErrorBag();
        // يمكنك هنا إعادة تعيين أي تغييرات لم تحفظ
    }

    // في المكون الخاص بك
public $showSuccess = false;

protected $listeners = ['showSuccessMessage' => 'showSuccessMessage'];

public function showSuccessMessage()
{
    $this->showSuccess = true;
    $this->dispatchBrowserEvent('message-shown');
}


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
        $target = AgencyTarget::where('agency_id', $this->agency->id)
            ->where('month', now()->startOfMonth())
            ->first();

        $this->monthlyTarget = $target?->target_amount;
        $this->currentTarget = $target?->target_amount;
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

              session()->flash('message', 'تم رفع الشعار بنجاح!');
             session()->flash('type', 'success');
        }
    }

    public function update()
    {
        $this->validate([
           'phone' => 'required|numeric|max:999999999',
           'landline' => 'required|string|max:30',
            'email' => 'required|email|unique:agencies,email,' . $this->agency->id,
            'address' => 'required|string',
            'description' => 'required|string',
            'monthlyTarget' => 'required|numeric|min:0',
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

        // تحقق من وجود هدف سابق
        $existingTarget = AgencyTarget::where('agency_id', $this->agency->id)
            ->where('month', now()->startOfMonth())
            ->first();

        if ($existingTarget && $this->monthlyTarget != $existingTarget->target_amount) {

             session()->flash('message',  'لا يمكن تعديل الهدف الشهري بعد تحديده لهذا الشهر.');
             session()->flash('type', 'error');

            return; // إيقاف المعالجة هنا
        }

  


        // إنشاء الهدف إذا لم يكن موجودًا
        if (!$existingTarget && $this->monthlyTarget !== null) {
            AgencyTarget::create([
                'agency_id' => $this->agency->id,
                'month' => now()->startOfMonth(),
                'target_amount' => $this->monthlyTarget,
            ]);
        }

        $this->agency = Agency::find($this->agency->id);
        $this->logo = null;
        $this->tempLogoUrl = null;

        $this->logoPreview = $this->agency->logo
            ? Storage::url($this->agency->logo) . '?v=' . now()->timestamp
            : asset('images/default-agency-logo.png');
        $this->currentTarget = $this->monthlyTarget;
         session()->flash('message',  'تم تعديل البيانات بنجاح');
         session()->flash('type', 'success');
        $this->editing = false;

    }




    public function render()
    {
        return view('livewire.agency.profile', [
            'logoPreview' => $this->logoPreview,
        ])->layout('layouts.agency');
    }
}
