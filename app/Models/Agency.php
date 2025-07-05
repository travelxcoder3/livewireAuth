<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

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
        'currency',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
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

    /**
     * إنشاء الصلاحيات الأساسية للوكالة
     */
    public function createBasicPermissions()
    {
        $permissionSeeder = new \Database\Seeders\PermissionSeeder();
        $permissionSeeder->createPermissionsForAgency($this->id);
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

        // ربط الدور بجميع الصلاحيات
        $role->givePermissionTo([
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            'reports.view', 'reports.export',
            'settings.view', 'settings.edit',
        ]);

        return $role;
    }
}
