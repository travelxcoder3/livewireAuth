<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'license_number',
        'commercial_record',
        'tax_number',
        'logo',
        'description',
        'status',
        'license_expiry_date',
        'max_users',
        'landline', 
        'currency',
        'subscription_start_date',
        'subscription_end_date',
        'theme_color',
        'parent_id', // تمت الإضافة هنا
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
    ];

    public function admin()
    {
        return $this->hasOne(User::class, 'agency_id')->whereHas('roles', function($q) {
            $q->where('name', 'agency-admin');
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'agency_id');
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isLicenseExpired()
    {
        return $this->license_expiry_date->isPast();
    }
    
    /**
     * إنشاء دور agency-admin للوكالة
     */
    public function createAgencyAdminRole()
    {
        $role = Role::firstOrCreate([
            'name' => 'agency-admin',
            'guard_name' => 'web',
            'agency_id' => $this->id,
        ]);

        // ربط الدور بجميع الصلاحيات العامة فقط (agency_id = null)
        $allPermissions = \Spatie\Permission\Models\Permission::whereNull('agency_id')->pluck('name')->toArray();
        $role->syncPermissions($allPermissions);

        return $role;
    }

    public function serviceTypes()
    {
        return $this->hasMany(ServiceType::class);
    }

    public function policies()
    {
        return $this->hasMany(AgencyPolicy::class);
    }

    /**
     * Get all branches for this agency (if main agency)
     */
    public function branches()
    {
        return $this->hasMany(Agency::class, 'parent_id');
    }

    /**
     * Get the main agency for this branch (if branch)
     */
    public function parent()
    {
        return $this->belongsTo(Agency::class, 'parent_id');
    }

    /**
     * Get all users for this agency and its branches
     */
    public function allUsersWithBranches()
    {
        $agencyIds = $this->branches()->pluck('id')->toArray();
        $agencyIds[] = $this->id;
        return \App\Models\User::whereIn('agency_id', $agencyIds)->get();
    }

    // داخل موديل Agency
    public function targets()
    {
        return $this->hasMany(\App\Models\AgencyTarget::class);
    }

}
