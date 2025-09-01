<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AutoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $abilities = ['view','create','edit','delete','export'];
        $modules = [
            'dashboard','users','roles','permissions','service_types','providers',
            'customers','customer-accounts','customer-credit-balances',
            'sales','reports.sales','reports.accounts','reports.customer-accounts',
            'reports.employee-sales','reports.provider-sales','reports.customer-sales',
            'reports.provider-accounts','reports.quotations','reports.provider-ledger',
            'audit.accounts','statements.customers','statements.customer','statements.employees','statements.employee','statements.providers','statements.provider',
            'sales-invoices','invoices','commissions','monthly-targets',
            'collections','collections.all','collection.details','employee-collections','employee-collections.all','employee-collections.show',
            'approval-sequences','approvals.index',
            'hr.employees','hr.employee-files',
            'quotation','quotations.view','quotations.pdf',
            'policies','policies.view',
            'dynamic-lists','settings.sale-edit-window',
            'backups','provider-invoices','provider-invoice-overview','provider-detailed-invoices',
            'provider-invoices-pdf','customer-invoices-pdf',
            'system.update-theme','invoices.download','provider-invoices.export',
        ];

        foreach ($modules as $m) {
            foreach ($abilities as $a) {
                Permission::firstOrCreate(['name' => "$m.$a"]);
            }
        }
    }
}
