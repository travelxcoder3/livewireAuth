<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Agency;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Carbon;
use Livewire\WithFileUploads;
use App\Models\AgencyTarget;

class AddAgency extends Component
{
     use WithFileUploads;
    // بيانات الوكالة
    public $agency_name;
    public $agency_email;
    public $agency_phone;
    public $landline;
    public $agency_address;
    public $license_number;
    public $commercial_record;
    public $tax_number;
    public $license_expiry_date;
    public $description;
    public $currency = 'SAR';
    public $status = 'active';
    public $parent_id;
    public $mainAgencies = [];
    public $monthly_sales_target;
    public $isMainAgency = true; // إضافة متغير لتحديد نوع الوكالة
    // ✅ حقول الاشتراك
    public $subscription_start_date;
    public $subscription_end_date;

    // بيانات أدمن الوكالة
    public $admin_name;
    public $admin_email;
    public $admin_password;
    public $admin_password_confirmation;

    public $successMessage;
    public $max_users = 3;

    public $logo;
    protected function rules()
    {
        $rules = [
            'agency_name' => 'required|string|max:255',
            'agency_email' => 'required|email|unique:agencies,email',
            'agency_phone' => 'required|string|max:30',
            'landline' => 'nullable|string|max:30',
            'agency_address' => 'required|string|max:255',
            'license_number' => 'required|string|unique:agencies,license_number',
            'commercial_record' => 'required|string|unique:agencies,commercial_record',
            'tax_number' => 'required|string|unique:agencies,tax_number',
            'license_expiry_date' => 'required|date',
            'description' => 'nullable|string',
            'subscription_start_date' => 'required|date|before_or_equal:subscription_end_date',
            'subscription_end_date' => 'required|date|after_or_equal:subscription_start_date',
            'currency' => 'required|string|max:10',
            // حذف شرط main_branch_name
            'max_users' => 'required|integer|min:1|max:100',
            'logo' => 'nullable|image|max:2048',
            'admin_name' => 'required|string|max:255',
            'admin_email' => ['required','email','unique:users,email'],
            'admin_password' => 'required|string|min:6',
            'admin_password_confirmation' => 'required|same:admin_password',
        ];

        // إضافة validation للـ parent_id إذا كانت الوكالة فرعية
        if (!$this->isMainAgency) {
            $rules['parent_id'] = 'required|exists:agencies,id';
        } else {
            $rules['parent_id'] = 'nullable|exists:agencies,id';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'parent_id.required' => 'يجب اختيار الوكالة الرئيسية عندما تكون الوكالة فرعية.',
            'parent_id.exists' => 'الوكالة الرئيسية المختارة غير موجودة.',
        ];
    }

    public function mount()
    {
        $this->mainAgencies = \App\Models\Agency::whereNull('parent_id')->get();
    }

    public function save()
{
    $this->validate();

    // تأكيد على التعامل الصحيح مع نوع الوكالة
    if ($this->isMainAgency) {
        $this->parent_id = null;
    } else {
        if (empty($this->parent_id)) {
            $this->addError('parent_id', 'يجب اختيار الوكالة الرئيسية.');
            return;
        }
        $this->parent_id = (int) $this->parent_id;
    }

    $logoPath = null;
    if ($this->logo) {
        $logoPath = $this->logo->store('logos', 'public');
    }

    DB::beginTransaction();
    try {
        $agency = Agency::create([
            'name' => $this->agency_name,
            'email' => $this->agency_email,
            'phone' => $this->agency_phone,
            'landline' => $this->landline,
            'address' => $this->agency_address,
            'license_number' => $this->license_number,
            'commercial_record' => $this->commercial_record,
            'tax_number' => $this->tax_number,
            'license_expiry_date' => $this->license_expiry_date,
            'description' => $this->description,
            'currency' => $this->currency,
            'status' => $this->status,
            'logo' => $logoPath,
            'max_users' => $this->max_users,
            'subscription_start_date' => $this->subscription_start_date,
            'subscription_end_date' => $this->subscription_end_date,
            'parent_id' => $this->parent_id,
        ]);

        // صلاحيات ودور الأدمن
        $permissionSeeder = new PermissionSeeder();
        $permissionSeeder->createPermissionsForAgency($agency->id);

        $agencyAdminRole = Role::firstOrCreate([
            'name' => 'agency-admin',
            'guard_name' => 'web',
            'agency_id' => $agency->id,
        ]);

        $allPermissions = \Spatie\Permission\Models\Permission::where('agency_id', $agency->id)
            ->orWhereNull('agency_id')
            ->pluck('name')
            ->toArray();

        $agencyAdminRole->syncPermissions($allPermissions);

        $admin = User::create([
            'name' => $this->admin_name,
            'email' => $this->admin_email,
            'password' => Hash::make($this->admin_password),
            'agency_id' => $agency->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole($agencyAdminRole);

        DB::commit();

        $this->mainAgencies = Agency::whereNull('parent_id')->get();

        // ✅ تحديث reset
        $this->reset([
    'agency_name', 'agency_email', 'agency_phone', 'landline',
    'agency_address', 'license_number', 'commercial_record', 'tax_number',
    'license_expiry_date', 'description', 'currency',
    'subscription_start_date', 'subscription_end_date',
    'max_users', 'logo',
    'admin_name', 'admin_email', 'admin_password', 'admin_password_confirmation',
    'parent_id', 'isMainAgency',
]);


        $this->successMessage = 'تمت إضافة الوكالة بنجاح مع تعيين أدمن خاص بها.';
    } catch (\Exception $e) {
        DB::rollBack();
        $this->addError('general', 'حدث خطأ أثناء إضافة الوكالة: ' . $e->getMessage());
    }
}


    public function render()
    {
        return view('livewire.admin.add-agency')
            ->layout('layouts.admin')
            ->title('إضافة وكالة جديدة - نظام إدارة وكالات السفر');
    }
}
