<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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


// ============================
// 🌐 المسارات العامة والمصادقة
// ============================

Route::get('/', fn () => view('welcome'));

Route::get('/login', Login::class)->name('login');

Route::get('reset-password/{token}', fn ($token) => 'This is a fake reset password page for testing. Token: ' . $token)
    ->name('password.reset');

Route::get('/forgot-password', fn () => view('livewire.auth.forgot-password'))
    ->name('password.request');

Route::get('/register', fn () => redirect('/')->with('error', 'إنشاء الحسابات يتم فقط عن طريق السوبر أدمن أو أدمن الوكالة.'));

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

Route::prefix('agency')->name('agency.')->middleware(['auth', 'mustChangePassword'])->group(function () {
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
// ============================
    // 🧑‍💼 قسم الحسابات داخل لوحة الوكالة
    // ============================
        Route::prefix('accounts')->group(function () {
                Route::get('/report/pdf', [\App\Http\Controllers\Agency\AccountController::class, 'generatePdfReport'])
                    ->name('accounts.report.pdf');
                    
                Route::get('/report/excel', [\App\Http\Controllers\Agency\AccountController::class, 'generateExcelReport'])
                    ->name('accounts.report.excel');
            });
        
            Route::get('/accounts', Accounts::class)->name('accounts');
  // ============================
    // 🧑‍💼 قسم التحصيلات داخل لوحة الوكالة
    // ============================
            Route::get('/collections', \App\Livewire\Agency\Collections::class)
            ->name('collections');
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
    // رابط الموافقات للوكالة الرئيسية فقط
    Route::get('/approval-requests', \App\Livewire\Admin\ApprovalRequests::class)
        ->name('approval-requests')
        ->middleware('role:agency-admin');
    // === رابط الموافقات للوكالة (جديد) ===
    Route::get('/approvals', \App\Livewire\Agency\ApprovalRequests::class)->name('approvals.index');
});
// Route::post('/update-theme', [ThemeController::class, 'updateTheme'])
//     ->middleware(['auth', 'agency']);
Route::post('/update-theme', [ThemeController::class, 'updateTheme'])
->middleware(['auth']);

Route::get('/commissions', Commissions::class)->name('agency.commissions');