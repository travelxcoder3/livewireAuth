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

class AddAgency extends Component
{
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
    public $main_branch_name;
    public $status = 'active';

    // ✅ حقول الاشتراك
    public $subscription_start_date;
    public $subscription_end_date;

    // بيانات أدمن الوكالة
    public $admin_name;
    public $admin_email;
    public $admin_password;

    public $successMessage;
    public $max_users = 3;


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
            'main_branch_name' => 'required|string|max:255',
            'max_users' => 'required|integer|min:1|max:100',

            'admin_name' => 'required|string|max:255',
            'admin_email' => ['required','email','unique:users,email'],
            'admin_password' => 'required|string|min:6',
        ];
    }

    public function save()
    {
        $this->validate();

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
                'main_branch_name' => $this->main_branch_name,
                'status' => $this->status,
                'logo' => null,
                'max_users' => $this->max_users,

                // ✅ تواريخ الاشتراك
                'subscription_start_date' => $this->subscription_start_date,
                'subscription_end_date' => $this->subscription_end_date,
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
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
                'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
                'reports.view', 'reports.export',
                'settings.view', 'settings.edit',
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
            $this->reset(['agency_name','agency_email','agency_phone', 'landline','agency_address','license_number','commercial_record','tax_number','license_expiry_date','description','currency', 'main_branch_name','subscription_start_date','subscription_end_date','admin_name','admin_email','admin_password']);
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
