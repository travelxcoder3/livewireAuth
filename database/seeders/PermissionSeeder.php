<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    // الصلاحيات الأساسية العامة لجميع الوكالات
    protected $basicPermissions = [
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'permissions.view',
        'permissions.create',
        'permissions.edit',
        'permissions.delete',
        // صلاحيات المبيعات
        'sales.view',
        'sales.create',
        'sales.edit',
        'sales.delete',
        'sales.report',
        'sales.reports.view',
        // صلاحيات الموظفين
        'employees.view',
        'employees.create',
        'employees.edit',
        'employees.delete',
        // صلاحيات المزودين
        'providers.view',
        'providers.create',
        'providers.edit',
        'providers.delete',
        // صلاحيات الوسطاء
        'intermediaries.view',
        'intermediaries.create',
        'intermediaries.edit',
        'intermediaries.delete',
        // صلاحيات العملاء
        'customers.view',
        'customers.create',
        'customers.edit',
        'customers.delete',
        // صلاحيات الخدمات
        'service_types.view',
        'service_types.create',
        'service_types.edit',
        'service_types.delete',
        'service_types.archive',
        'service_types.restore',
        // صلاحيات الملفات والمرفقات
        'attachments.view',
        'attachments.upload',
        'attachments.delete',
        // صلاحيات العملات
        'currency.view',
        'currency.edit',
        // صلاحيات الحسابات المالية
        'accounts.view',
        'accounts.create',
        'accounts.edit',
        'accounts.delete',
        // صلاحيات التسلسلات
        'sequences.view',
        'sequences.create',
        'sequences.edit',
        'sequences.delete',
        // صلاحيات الأقسام
        'departments.view',
        'departments.create',
        'departments.edit',
        'departments.delete',
        // صلاحيات الوظائف
        'positions.view',
        'positions.create',
        'positions.edit',
        'positions.delete',
        // صلاحيات الإشعارات
        'notifications.view',
        'notifications.send',
        'notifications.delete',
        // صلاحيات الموافقات
        'approvals.view',
        'approvals.manage',
        // إعدادات النظام
        'system.settings.view',
        'system.settings.edit',
        'theme.view',
        'theme.edit',
        // أرشفة واستعادة
        'users.archive',
        'users.restore',
        'employees.archive',
        'employees.restore',
        'customers.archive',
        'customers.restore',
        'providers.archive',
        'providers.restore',
        'service_types.archive',
        'service_types.restore',
        'sales.archive',
        'sales.restore',
        // صلاحيات الفروع
        'branches.view',
        'branches.create',
        'branches.edit',
        'branches.delete',
        // صلاحيات القوائم
        'lists.view',
        'lists.create',
        'lists.edit',
        'lists.delete',
        // صلاحيات إضافية مستخدمة في النظام
        'agency.profile.view',
        'dynamic-lists.view',
        'collection.view',
        // صلاحيات إضافية للحسابات
        'accounts.export',
        'accounts.print',
        'accounts.invoice',
        'agency.profile.edit',
        'debt.details.view',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الصلاحيات العامة مرة واحدة فقط
        $this->createGlobalPermissions();
    }

    /**
     * إنشاء الصلاحيات العامة لجميع الوكالات
     */
    public function createGlobalPermissions()
    {
        foreach ($this->basicPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * إنشاء الصلاحيات الأساسية لوكالة معينة (للتوافق مع الكود القديم)
     * @deprecated استخدم createGlobalPermissions بدلاً من هذه الدالة
     */
    public function createPermissionsForAgency($agencyId)
    {
        // الآن الصلاحيات عامة، لا حاجة لإنشاء صلاحيات خاصة لكل وكالة
        $this->createGlobalPermissions();
    }
} 