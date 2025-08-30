<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AddPermissions extends Seeder
{
    public function run(): void
    {
        $perms = [
            'collection.emp.details.view',
            'collection.employee.view',
            'reportProvider.view',
            'commission-policies.view',
            'reportQuotation.view',
            'backup.view',
            'month-goals.view',
            'review.view',
            'sales-review.view',
            'accounts-review.view',
            'invoices-review.view',
            'sales-invoices.view',
            'customer-invoices.view',
            'provider-invoices.view',
            'employees-manage.view',
            'manage-account.view',
            'account-statement.view',
            'customers-statement.view',
            'customer-statement.view',
            'providers-statement.view',
            'employee-statement.view',
            'reportCustomersSales.view',
            'policies.view',
            'obligations.view',
            
        ];

        foreach ($perms as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
