<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    // الصلاحيات الأساسية العامة لجميع الوكالات
    protected $basicPermissions = [
        'users.view',
        'users.edit',
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'permissions.view',
        // صلاحيات المبيعات
        'sales.view',
        'sales.create',
 
        'sales.report',
        'sales.export',
        'sales.reports.view',
        // صلاحيات الموظفين
        'employees.view',
        'employees.create',
        'employees.edit',
        // صلاحيات المزودين
        'providers.view',
        'providers.create',
        'providers.edit',
 
        // صلاحيات العملاء
        'customers.view',
        'customers.create',
        'customers.edit',
      
        // صلاحيات التسلسلات
        'sequences.view',
        'sequences.create',
        'sequences.edit',
       
        // صلاحيات الإشعارات
        'notifications.view',
        'notifications.send',
        'notifications.delete',
       
        // صلاحيات القوائم
        'lists.view',
        'lists.create',
        'lists.edit',
        'lists.delete',
        // صلاحيات إضافية مستخدمة في النظام
        'agency.profile.view',
        'collection.view',
        'collection.payment',
        'collection.details.view',
        // صلاحيات إضافية للحسابات
        'accounts.export',
        'accounts.print',
        'accounts.invoice',
        'agency.profile.edit',
        //صلاحيات عرض صفحه التقارير
        'reportsAccounts.view',
        'reportsSales.view',
        'reportCustomers.view',
        'reportCustomerAccounts.view',
        'reportEmployeeSales.view',
        //عرض السعر
        'quotations.view',
        // فواتير
        'invoices.view',

        
            'collection.employee.view',
            'reportProvider.view',
            'commission-policies.view',
            'reportQuotation.view',
            'backup.view',
            'month-goals.view',
            'sales-review.view',
            'accounts-review.view',
            'invoices-review.view',
            'sales-invoices.view',
            'customer-invoices.view',
            'provider-invoices.view',
            'employees-manage.view',
            'account-statement.view',
            'customers-statement.view',
            'providers-statement.view',
            'employee-statement.view',
            'reportCustomersSales.view',
            'policies.view',
            'obligations.view',
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