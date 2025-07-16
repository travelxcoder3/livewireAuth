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

    // ✅ حقول الاشتراك
    public $subscription_start_date;
    public $subscription_end_date;

    // بيانات أدمن الوكالة
    public $admin_name;
    public $admin_email;
    public $admin_password;

    public $successMessage;
    public $max_users = 3;

    public $logo;
    protected function rules()
    {
        return [
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
            'parent_id' => 'nullable|exists:agencies,id',
        ];
    }

    public function mount()
    {
        $this->mainAgencies = \App\Models\Agency::whereNull('parent_id')->get();
    }

    public function save()
    {
        $this->validate();

        // تحويل parent_id إلى integer أو null
        if ($this->parent_id === "" || $this->parent_id === null) {
            $this->parent_id = null;
        } else {
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

            // إنشاء الصلاحيات الأساسية للوكالة
            $permissionSeeder = new PermissionSeeder();
            $permissionSeeder->createPermissionsForAgency($agency->id);
            
            // إنشاء دور agency-admin خاص بالوكالة
            $agencyAdminRole = Role::firstOrCreate([
                'name' => 'agency-admin',
                'guard_name' => 'web',
                'agency_id' => $agency->id,
            ]);
            
            // ربط دور agency-admin بجميع الصلاحيات
            $agencyAdminRole->givePermissionTo([
                'sales.view', 'sales.create', 'sales.edit', 'sales.delete', 'sales.report',
                'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
                'providers.view', 'providers.create', 'providers.edit', 'providers.delete',
                'service_types.view', 'service_types.create', 'service_types.edit', 'service_types.delete',
                'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
                'lists.view', 'lists.create', 'lists.edit', 'lists.delete',
                'sequences.view', 'sequences.create', 'sequences.edit', 'sequences.delete',
                'agency.profile.view', 'agency.profile.edit',
                'currency.view', 'currency.edit',
                'system.settings.view', 'system.settings.edit',
                'theme.view', 'theme.edit',
                'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
                'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
                'positions.view', 'positions.create', 'positions.edit', 'positions.delete',
                'intermediaries.view', 'intermediaries.create', 'intermediaries.edit', 'intermediaries.delete',
                'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',
                'sales.reports.view',
            ]);
            
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
            $this->mainAgencies = \App\Models\Agency::whereNull('parent_id')->get();
            $this->reset(['agency_name','agency_email','agency_phone', 'landline','agency_address','license_number','commercial_record','tax_number','license_expiry_date','description','currency', 'subscription_start_date','subscription_end_date','admin_name','admin_email','admin_password']);
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
