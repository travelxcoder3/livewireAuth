<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewProviderApprovalRequest;
use Illuminate\Support\Facades\Notification;

class Providers extends Component
{
    public $providers;
    public $showModal = false;
    public $editMode = false;
    public $contact_info;

    public $providerId;
    public $name;
    public $type;
    public $service_item_id;
    public $services = [];
    
    protected $rules = [
        'name' => 'required|string|max:255',
        'type' => 'nullable|string|max:255',
        'contact_info' => 'nullable|regex:/^[0-9]+$/|min:7|max:20',
        'service_item_id' => 'nullable|exists:dynamic_list_items,id',
    ];
    
    protected $listeners = ['providerStatusUpdated' => 'fetchProviders'];

    public function mount()
    {
        $this->fetchProviders();
        $this->fetchServices();
    }
    
    public function fetchServices()
    {
         $this->services = \App\Models\DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة الخدمات');
        })
        ->where(function($query) {
            $query->where('created_by_agency', auth()->user()->agency_id)
                  ->orWhereNull('created_by_agency');
        })
        ->get();
    }
    
    

    public function fetchProviders()
    {
        $this->providers = \App\Models\Provider::with('service')
            ->where('agency_id', Auth::user()->agency_id)
            ->latest()
            ->get();
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
        $this->service_item_id = $provider->service_item_id;
        $this->editMode = true;
        $this->showModal = true;

    }

    public function saveProvider()
    {
        $this->validate();

        $user = Auth::user();
        $agency = $user->agency;

        if ($this->editMode && $this->providerId) {
            // تحديث المزود الحالي
            $provider = Provider::findOrFail($this->providerId);
            $provider->update([
                'name' => $this->name,
                'type' => $this->type,
                'contact_info' => $this->contact_info,
                'service_item_id' => $this->service_item_id,
                // لا تغير agency_id أو status هنا إلا إذا أردت منطق خاص
            ]);
        } else {
            // منطق الإضافة كما هو عندك
            if ($agency->parent_id) {
                // المستخدم في فرع: إضافة المزود بحالة pending + طلب موافقة
                $provider = Provider::create([
                    'agency_id' => $agency->id,
                    'name' => $this->name,
                    'type' => $this->type,
                    'contact_info' => $this->contact_info,
                    'service_item_id' => $this->service_item_id,
                    'status' => 'pending',
                ]);

                // جلب الوكالة الرئيسية
                $mainAgency = $agency->parent;
                \Log::info('قبل جلب التسلسل', ['mainAgency_id' => $mainAgency?->id]);
                // جلب تسلسل الموافقات الخاص بإضافة مزودين
                $approvalSequence = \App\Models\ApprovalSequence::where('agency_id', $mainAgency->id)
                    ->where('action_type', 'manage_provider')
                    ->first();
                \Log::info('بعد جلب التسلسل', ['approvalSequence' => $approvalSequence?->id]);
                if ($approvalSequence) {
                    $approvalRequest = \App\Models\ApprovalRequest::create([
                        'approval_sequence_id' => $approvalSequence->id,
                        'model_type' => \App\Models\Provider::class,
                        'model_id' => $provider->id,
                        'status' => 'pending',
                        'requested_by' => $user->id,
                        'agency_id' => $agency->id,
                    ]);
                    \Log::info('بعد إنشاء طلب الموافقة', ['approvalRequest' => $approvalRequest->id]);
                    $mainAdmins = \App\Models\User::where('agency_id', $mainAgency->id)
                        ->role('agency-admin')
                        ->get();
                    \Log::info('الأدمن المستهدفون', ['ids' => $mainAdmins->pluck('id')->toArray()]);
                    if ($mainAdmins->count() > 0) {
                        \Illuminate\Support\Facades\Notification::send($mainAdmins, new \App\Notifications\NewProviderApprovalRequest($approvalRequest));
                        \Log::info('تم إرسال إشعار NewProviderApprovalRequest للأدمن');
                    } else {
                        \Log::warning('لا يوجد أدمن في الوكالة الرئيسية لإرسال الإشعار لهم');
                    }
                } else {
                    \Log::info('لا يوجد تسلسل موافقات');
                }
            } else {
                Provider::create([
                    'agency_id' => $agency->id,
                    'name' => $this->name,
                    'type' => $this->type,
                    'contact_info' => $this->contact_info,
                    'service_item_id' => $this->service_item_id,
                    'status' => 'approved',
                ]);
            }
        }
        $this->showModal = false;
        $this->fetchProviders();
         session()->flash('message', $this->editMode ? 'تم تحديث المزود بنجاح' : 'تم إضافة المزود بنجاح');
session()->flash('type', 'success');

    }

   

 public function resetForm()
{
    $this->providerId = null;
    $this->name = '';
    $this->type = '';
    $this->contact_info = '';
    $this->service_item_id = '';

}

    public function render()
    {
        return view('livewire.agency.providers')
            ->layout('layouts.agency')
            ->title('إدارة المزودين');
    }
}
