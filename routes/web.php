<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\AccountsReportController;

// Admin Components
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Agencies;
use App\Livewire\Admin\AddAgency;
use App\Livewire\Admin\EditAgency;
use App\Livewire\Admin\DeleteAgency;

// Agency Components
use App\Livewire\Agency\Dashboard as AgencyDashboard;
use App\Livewire\Agency\Users;
use App\Livewire\Agency\Roles;
use App\Livewire\Agency\Permissions;
use App\Livewire\Agency\ServiceTypes;
use App\Livewire\Agency\ChangePassword;
use App\Livewire\Agency\Providers;
use App\Livewire\Agency\AddCustomer;
use App\Livewire\Agency\Profile;

// HR Components
use App\Livewire\HR\EmployeeIndex;
use App\Livewire\HR\EmployeeCreate;
use App\Livewire\HR\EmployeeEdit;

// Auth Components
use App\Livewire\Auth\Login;

// Sales & Reports
use App\Livewire\Sales\Index as SalesIndex;
use App\Http\Controllers\Agency\ReportController;

use App\Livewire\Admin\DynamicLists;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\SystemSettingsController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

use App\Livewire\Agency\Accounts;
use App\Livewire\Agency\AgencyPolicies;

use App\Livewire\Agency\Commissions;
use App\Livewire\Agency\Obligations\Index;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\CustomerFollowUpReportController;

use App\Livewire\Agency\Reports\CustomerAccountDetails;
use App\Http\Controllers\CustomerAccountReportController;

use App\Livewire\Agency\AccountHistories;
use App\Livewire\Agency\Reports\EmployeeServiceSales;

//customer invoice details
use App\Livewire\Agency\CustomerDetailedInvoices;
use App\Livewire\Agency\CustomerInvoiceOverview;
use App\Http\Controllers\Agency\CustomerInvoicePrintController;
use App\Livewire\Agency\Quotations\ShowQuotation;
use App\Http\Controllers\Agency\QuotationController;

// ============================
// 🌐 المسارات العامة والمصادقة
// ============================

// routes/web.php
use App\Livewire\Agency\Statements\CustomersList;
use App\Livewire\Agency\Statements\CustomerStatement; 
use App\Http\Controllers\Agency\StatementPdfController;
Route::get('/', fn() => view('welcome'));

Route::get('/login', Login::class)->name('login');

Route::get('reset-password/{token}', fn($token) => 'This is a fake reset password page for testing. Token: ' . $token)
    ->name('password.reset');

Route::get('/forgot-password', fn() => view('livewire.auth.forgot-password'))
    ->name('password.request');

Route::get('/register', fn() => redirect('/')->with('error', 'إنشاء الحسابات يتم فقط عن طريق السوبر أدمن أو أدمن الوكالة.'));

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');


// ============================
// 👑 مسارات السوبر أدمن
// ============================

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('/agencies', Agencies::class)->name('agencies');
    Route::get('/agencies/add', AddAgency::class)->name('add-agency');
    Route::get('/agencies/{agency}/edit', EditAgency::class)->name('edit-agency');
    Route::get('/agencies/{agency}/delete', DeleteAgency::class)->name('delete-agency');
    Route::get('/dynamic-lists', DynamicLists::class)
        ->name('dynamic-lists');
    Route::post('/system/update-theme', [SystemSettingsController::class, 'updateTheme'])
        ->name('system.update-theme');
});


// ============================
// 🏢 مسارات أدمن الوكالة
// ============================

Route::prefix('agency')->name('agency.')->middleware(['auth', 'mustChangePassword', 'active.user'])->group(function () {
    Route::get('/dashboard', AgencyDashboard::class)->name('dashboard');
    Route::get('/users', Users::class)->name('users');
    Route::get('/roles', Roles::class)->name('roles');
    Route::get('/permissions', Permissions::class)->name('permissions');
    Route::get('/service-types', ServiceTypes::class)->name('service_types');
    Route::get('/providers', Providers::class)->name('providers');
    Route::get('/customers/add', AddCustomer::class)->name('customers.add');
    Route::get('/profile', Profile::class)->name('profile');
    Route::get('/dynamic-lists', \App\Livewire\Agency\DynamicLists::class)->name('dynamic-lists');
    Route::get('/change-password', ChangePassword::class)->name('change-password');
    Route::get('/obligations', Index::class)
        ->name('obligations');
        Route::get('/obligations-view',\App\Livewire\Agency\ObligationsView::class
        )->name('obligations-view');
    
    Route::get('/customer-detailed-invoices', CustomerDetailedInvoices::class)
        ->name('customer-detailed-invoices');
    Route::get('/customer-invoices/{customer}', CustomerInvoiceOverview::class)
        ->name('customer-invoice-overview');
    Route::get('/customer-invoices/{customer}/print', [CustomerInvoicePrintController::class, 'printSelected'])
        ->name('customer-invoices.print');

            Route::get('/customer-accounts/{customer}/history', \App\Livewire\Agency\AccountHistoryDetails::class)
                ->name('customer-accounts.details');
                
     Route::get('/customer-accounts', AccountHistories::class)
        ->name('customer-accounts');
    
        Route::get('/customer-credit-balances', \App\Livewire\Agency\CustomerCreditBalances::class)
        ->name('customer-credit-balances');
    // ✅ واجهة المبيعات الخاصة بأدمن الوكالة
    Route::get('/sales', SalesIndex::class)->name('sales.index');
    // ✅ تقارير المبيعات PDF و Excel
    Route::get('/sales/report/pdf', [\App\Http\Controllers\Agency\ReportController::class, 'salesPdf'])
        ->name('sales.report.pdf');
    // تقرير Excel
    Route::get('/excel', function (\Illuminate\Http\Request $request) {
        $fields = $request->input('fields');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return Excel::download(
            new \App\Exports\SalesExport($fields, $startDate, $endDate),
            'sales-report.xlsx'
        );
    })->name('sales.report.excel');
    Route::get('/sales/report-preview', function () {
        return view('livewire.sales.report-preview');
    })->name('sales.report.preview');
    // ============================
    // 🧑‍💼 قسم الحسابات داخل لوحة الوكالة
    // ============================
    Route::prefix('accounts')->group(function () {
        Route::get('/report/pdf', [\App\Http\Controllers\Agency\AccountController::class, 'generatePdfReport'])
            ->name('accounts.report.pdf');

        Route::get('/report/excel', [\App\Http\Controllers\Agency\AccountController::class, 'generateExcelReport'])
            ->name('accounts.report.excel');
    });
      Route::get('/quotations/{quotation}/view', [\App\Http\Controllers\Agency\QuotationController::class,'view'])
        ->name('quotations.view');
    Route::get('/quotations/{quotation}/pdf',  [\App\Http\Controllers\Agency\QuotationController::class,'pdf'])
        ->name('quotations.pdf');
 Route::get('/statements/customers', CustomersList::class)
        ->name('statements.customers');
Route::get('/statements/customers/{customer}/pdf', [StatementPdfController::class, 'download'])
    ->name('statements.customer.pdf');
    Route::get('/statements/customers/{customer}', CustomerStatement::class)
        ->name('statements.customer');
    Route::get('/accounts', Accounts::class)->name('accounts');
    // ============================
    // 🧑‍💼 قسم التحصيلات داخل لوحة الوكالة
    // ============================
    Route::get('/collections', \App\Livewire\Agency\Collections::class)
        ->name('collections');
    Route::get('/collections/all', \App\Livewire\Agency\AllCollections::class)
        ->name('collections.all');
    Route::get('/collections/{sale}', \App\Livewire\Agency\ShowCollectionDetails::class)
        ->name('collection.details');
    // ============================
    // 🧑‍💼 قسم التسلسلات داخل لوحة الوكالة
    // ============================
    Route::get('/approval-sequences', \App\Livewire\Agency\ApprovalSequences::class)
        ->name('approval-sequences');
    // ============================
    // 🧑‍💼 قسم الموارد البشرية داخل لوحة الوكالة
    // ============================
    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/employees', EmployeeIndex::class)->name('employees.index');
        Route::get('/employees/create', EmployeeCreate::class)->name('employees.create');
        Route::get('/employees/edit/{employee}', EmployeeEdit::class)->name('employees.edit');
    });
    Route::get('/policies', AgencyPolicies::class)->name('policies');
    Route::get('/policies/view', \App\Livewire\Agency\PoliciesView::class)->name('policies.view');
    Route::get('/quotation', ShowQuotation::class)->name('quotation');
    Route::post('/quotation/pdf', [QuotationController::class, 'download'])
    ->name('quotation.pdf');
    // === رابط الموافقات للوكالة (جديد) ===
    Route::get('/approval-requests', \App\Livewire\Agency\ApprovalRequests::class)->name('approvals.index');
    // ============================
    // 🧑‍💼 قسم التقارير داخل لوحة الوكالة
    // ============================
    Route::prefix('reports')->group(function () {
        Route::get('/accounts', \App\Livewire\Agency\Reports\AccountsReport::class)->name('reports.accounts');
        Route::get('/accounts/pdf', [AccountsReportController::class, 'downloadPdf'])->name('reports.accounts.pdf');

        Route::get('/sales', \App\Livewire\Agency\Reports\SalesReport::class)->name('reports.sales');
        Route::get('/sales/pdf', [SalesReportController::class, 'downloadPdf'])->name('reports.sales.pdf');
        // تقرير تتبع العملاء
        Route::get('/customers-follow-up', \App\Livewire\Agency\Reports\CustomerFollowUpReport::class)
            ->name('reports.customers-follow-up');
        Route::get('customers-follow-up/pdf', [CustomerFollowUpReportController::class, 'downloadPdf'])
            ->name('reports.customers-follow-up.pdf');
        Route::get('customer-accounts', \App\Livewire\Agency\Reports\CustomerAccounts::class)
            ->name('reports.customer-accounts');
        Route::get('customer-accounts/{id}/details', CustomerAccountDetails::class)
            ->name('reports.customer-accounts.details');
        Route::get('customer-accounts/{id}/pdf', [CustomerAccountReportController::class, 'generatePdf'])
            ->name('reports.customer-accounts.pdf');
        Route::get('employee-sales', \App\Livewire\Agency\Reports\EmployeeSalesReport::class)
            ->name('reports.employee-sales');
        
        Route::get('employee-sales/pdf', [\App\Livewire\Agency\Reports\EmployeeSalesReport::class, 'exportToPdf'])
            ->name('reports.employee-sales.pdf');
        
        Route::get('employee-sales/excel', [\App\Livewire\Agency\Reports\EmployeeSalesReport::class, 'exportToExcel'])
            ->name('reports.employee-sales.excel');

        Route::get('reports/employee-sales/print/{sale}', [\App\Livewire\Agency\Reports\EmployeeSalesReport::class, 'printPdf'])
            ->name('.reports.employee-sales.sale-pdf');
        
        

    });
});
// Route::post('/update-theme', [ThemeController::class, 'updateTheme'])
//     ->middleware(['auth', 'agency']);
Route::post('/update-theme', [ThemeController::class, 'updateTheme'])
    ->middleware(['auth']);

Route::get('/commissions', Commissions::class)->name('agency.commissions');

// في routes/web.php
Route::get('/invoices/{invoice}/download', function (App\Models\Invoice $invoice) {
    return (new App\Livewire\Agency\Accounts())->downloadBulkInvoicePdf($invoice->id);
})->name('invoices.download');




