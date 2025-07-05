<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Agencies;
use App\Livewire\Admin\AddAgency;
use App\Livewire\Admin\EditAgency;
use App\Livewire\Admin\DeleteAgency;
use App\Livewire\Agency\Dashboard as AgencyDashboard;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('reset-password/{token}', function ($token) {
    return 'This is a fake reset password page for testing. Token: ' . $token;
})->name('password.reset');

Route::get('/login', \App\Livewire\Auth\Login::class)->name('login');

Route::get('/register', function () {
    return redirect('/')->with('error', 'إنشاء الحسابات يتم فقط عن طريق السوبر أدمن أو أدمن الوكالة.');
});

// مسارات السوبر أدمن - المكونات الموجودة فقط
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/agencies', Agencies::class)->name('agencies');
    Route::get('/agencies/add', AddAgency::class)->name('add-agency');
    Route::get('/agencies/{agency}/edit', EditAgency::class)->name('edit-agency');
    Route::get('/agencies/{agency}/delete', DeleteAgency::class)->name('delete-agency');
});

// مسارات أدمن الوكالة
Route::prefix('agency')->name('agency.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', AgencyDashboard::class)->name('dashboard');
    Route::get('/users', \App\Livewire\Agency\Users::class)->name('users');
    Route::get('/roles', \App\Livewire\Agency\Roles::class)->name('roles');
    Route::get('/permissions', \App\Livewire\Agency\Permissions::class)->name('permissions');
});

// مسار تسجيل الخروج
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

