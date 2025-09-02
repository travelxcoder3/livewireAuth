<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class AutoPermission
{
    // أفعال قياسية → قدرات عامة
    protected array $abilityMap = [
        'index'=>'view','show'=>'view','list'=>'view',
        'create'=>'create','store'=>'create',
        'edit'=>'edit','update'=>'edit',
        'destroy'=>'delete','delete'=>'delete',
        'pdf'=>'export','excel'=>'export','export'=>'export',
        'print'=>'export','download'=>'export','restore'=>'export','run'=>'export',
    ];

    // مسارات يسمح بها بدون فحص صلاحية
    protected array $bypass = [
        'login','logout','password.*',
        'agency.dashboard',
        'agency.change-password',
        'agency.notifications.index',
        'agency.obligations-view',
        'agency.policies.view',
        
    ];

    // استثناءات: route.name => permission.name  (كلها من قائمة الـ 62)
    protected array $overrides = [
        // أساسية
        'agency.users'                     => 'users.view',
        'agency.roles'                     => 'roles.view',
        'agency.permissions'               => 'permissions.view',
        'agency.service_types'             => 'lists.view',
        'agency.providers'                 => 'providers.view',
        'agency.customers.add'             => 'customers.create',
        'agency.dynamic-lists'             => 'lists.view',
        'agency.obligations'               => 'obligations.view',
        'agency.obligations-view'          => 'obligations.view',
        'agency.profile'                   => 'agency.profile.view',

        // فواتير وتاريخ عملاء
        'agency.customer-detailed-invoices'=> 'customer-invoices.view',
        'agency.customer-invoice-overview' => 'customer-invoices.view',
        'agency.customer-invoices.print'   => 'customer-invoices.view',
        'agency.customer-accounts'         => 'reportCustomerAccounts.view',
        'agency.customer-accounts.details' => 'reportCustomerAccounts.view',
        'agency.customer-credit-balances'  => 'customers-statement.view',

        // تقارير المبيعات
        'agency.sales.report.preview'      => 'sales.report',

        // الحسابات
        'agency.accounts.report.pdf'       => 'accounts.export',
        'agency.accounts.report.excel'     => 'accounts.export',

        // عروض الأسعار
        'agency.quotations.view'           => 'quotations.view',
        'agency.quotations.pdf'            => 'quotations.view',
        'agency.quotation'                 => 'quotations.view',
        'agency.quotation.pdf'             => 'quotations.view',

        // كشوفات
        'agency.statements.customers'      => 'customers-statement.view',
        'agency.statements.customer'       => 'customers-statement.view',
        'agency.statements.customer.pdf'   => 'customers-statement.view',
        'agency.statements.employees'      => 'employee-statement.view',
        'agency.statements.employee'       => 'employee-statement.view',
        'agency.statements.employee.pdf'   => 'employee-statement.view',
        'agency.statements.providers'      => 'providers-statement.view',
        'agency.statements.provider'       => 'providers-statement.view',
        'agency.statements.provider.pdf'   => 'providers-statement.view',

        // أهداف شهرية وتحليل
        'agency.monthly-targets'           => 'month-goals.view',
        'agency.sales-review'              => 'sales-review.view',
        'agency.invoices.review'           => 'invoices-review.view',

        // تحصيلات
        'agency.collections'               => 'collection.view',
        'agency.collections.all'           => 'collection.view',
        'agency.collection.details'        => 'collection.view',
        'agency.employee-collections'      => 'collection.employee.view',
        'agency.collection.details.employee'=> 'collection.employee.view',
        'agency.employee-collections.all'  => 'collection.employee.view',
        'agency.employee-collections.show' => 'collection.employee.view',

        // تسلسلات واعتمادات
        'agency.approval-sequences'        => 'sequences.view',
        'agency.approvals.index'           => 'sequences.view',

        // HR
        // HR
        'agency.hr.employee-files'         => 'employees-manage.view',
        'agency.audit.accounts'            => 'accounts-review.view',
        'agency.sales-invoices'            => 'sales-invoices.view',

        // مزودون
        'agency.provider-detailed-invoices'=> 'provider-invoices.view',
        'agency.provider-invoice-overview' => 'provider-invoices.view',

        // سياسات
        'agency.policies'                  => 'policies.view',
        'agency.policies.view'             => 'policies.view',
        'agency.commission-policies'       => 'policies.view',
        'agency.settings.sale-edit-window' => 'policies.view',
        'agency.system.update-theme'       => 'agency.profile.edit',

        // تقارير لوحة الوكالة
        'agency.reports.accounts'                  => 'accounts-review.view',
        'agency.reports.accounts.pdf'              => 'accounts.export',
        'agency.reports.sales'                     => 'reportsSales.view',
        'agency.reports.sales.pdf'                 => 'sales.export',
      // تقارير لوحة الوكالة
'agency.reports.customers-follow-up'       => 'reportCustomerAccounts.view',
'agency.reports.customers-follow-up.pdf'   => 'reportCustomerAccounts.view',
// (اختياري إن أردت إبقاء القديم)
'agency.reports.customers-follow-up.index' => 'reportCustomerAccounts.view',

        'agency.reports.customer-accounts'         => 'reportCustomerAccounts.view',
        'agency.reports.customer-accounts.details' => 'reportCustomerAccounts.view',
        'agency.reports.customer-accounts.pdf'     => 'reportCustomerAccounts.view',
        'agency.reports.employee-sales'            => 'reportEmployeeSales.view',
        'agency.reports.employee-sales.pdf'        => 'reportEmployeeSales.view',
        'agency.reports.employee-sales.excel'      => 'reportEmployeeSales.view',
        'agency.reports.employee-sales.sale-pdf'   => 'reportEmployeeSales.view',
        'agency.reports.provider-sales'            => 'reportProvider.view',
        'agency.reports.provider-sales.pdf'        => 'reportProvider.view',
        'agency.reports.provider-sales.excel'      => 'reportProvider.view',
        'agency.reports.customer-sales'            => 'reportCustomersSales.view',
        'agency.reports.customer-sales.pdf'        => 'reportCustomersSales.view',
        'agency.reports.customer-sales.excel'      => 'reportCustomersSales.view',
        'agency.reports.provider-accounts'         => 'providers-statement.view',
        'agency.reports.provider-accounts.details' => 'providers-statement.view',
        'agency.reports.provider-accounts.pdf'     => 'providers-statement.view',
        'agency.reports.provider-ledger'           => 'providers-statement.view',
        'agency.reports.quotations'                => 'reportQuotation.view',
        'agency.reports.quotations.pdf'            => 'reportQuotation.view',
        'agency.notifications.index'               => 'notifications.view',

        // نسخ احتياطي
        'agency.backups.index'             => 'backup.view',
        'agency.backups.store'             => 'backup.view',
        'agency.backups.download'          => 'backup.view',
        'agency.backups.restore'           => 'backup.view',
        'agency.backups.restore_existing'  => 'backup.view',

        // مسارات مفردة خارج المجموعة
        'agency.commissions'               => 'policies.view',
        'agency.invoices.download'         => 'invoices-review.view',
        'agency.provider-invoices.export'  => 'provider-invoices.view',
        'agency.customer-invoices-pdf.print'=> 'customer-invoices.view',
        'agency.customer-invoices-bulk.print'=> 'customer-invoices.view',
    ];

    protected array $adminRoles = ['super-admin','agency-admin'];

    public function handle($request, Closure $next)
    {
        $route = optional($request->route())->getName();
        if (!$route) abort(404);

        if (Str::is($this->bypass, $route)) return $next($request);

        $user = $request->user();
        if ($user && $user->hasAnyRole($this->adminRoles)) return $next($request);

        // استثناء صريح
        if (isset($this->overrides[$route])) {
            abort_unless($user?->can($this->overrides[$route]), 403);
            return $next($request);
        }

        // اشتقاق تلقائي (للروتات القياسية مثل ...employees.index)
        $parts = explode('.', $route);
        $action = array_pop($parts);       // index|edit|pdf...
        $module = array_pop($parts);       // employees|sales|...

        $baseAction = Str::before($action, '-');
        $ability = $this->abilityMap[$baseAction] ?? null;
        if (!$module || !$ability) abort(403);

        $permission = "{$module}.{$ability}";
        abort_unless($user && $user->can($permission), 403);

        return $next($request);
    }
}
