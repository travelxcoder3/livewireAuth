<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Database\Seeders\PermissionSeeder;

class Dashboard extends Component
{
    public function mount()
    {
        
        // التحقق من أن المستخدم سوبر أدمن
        if (!Auth::user()->hasRole('super-admin')) {
            return redirect('/login');
        }
    }

    public function getTotalAgenciesProperty()
    {
        return Agency::count();
    }

    public function getActiveAgenciesProperty()
    {
        return Agency::where('status', 'active')->count();
    }

    public function getTotalUsersProperty()
    {
        return User::whereDoesntHave('roles', function($query) {
            $query->where('name', 'super-admin');
        })->count();
    }

    public function getRecentAgenciesProperty()
    {
        return Agency::latest()->take(5)->get();
    }

    public function getRecentUsersProperty()
    {
        return User::whereDoesntHave('roles', function($query) {
            $query->where('name', 'super-admin');
        })
            ->with('agency')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getExpiringLicensesProperty()
    {
        return Agency::where('license_expiry_date', '<=', now()->addDays(30))
            ->where('status', 'active')
            ->get();
    }

    public function createPermissionsForAllAgencies()
    {
        $agencies = Agency::all();
        $permissionSeeder = new PermissionSeeder();
        
        // إنشاء الصلاحيات العامة مرة واحدة
        $permissionSeeder->createGlobalPermissions();
        
        // إنشاء دور agency-admin لكل وكالة
        foreach ($agencies as $agency) {
            $agency->createAgencyAdminRole();
        }
        
        session()->flash('success', "تم إنشاء الصلاحيات العامة وربطها بـ {$agencies->count()} وكالة بنجاح");
    }

    public function createPermissionsForExistingAgencies()
    {
        $agencies = Agency::all();
        $permissionSeeder = new PermissionSeeder();
        
        // إنشاء الصلاحيات العامة مرة واحدة
        $permissionSeeder->createGlobalPermissions();
        
        // إنشاء دور agency-admin لكل وكالة
        foreach ($agencies as $agency) {
            $agency->createAgencyAdminRole();
        }
        
        session()->flash('success', "تم إنشاء الصلاحيات العامة وربطها بـ {$agencies->count()} وكالة موجودة بنجاح");
    }

    public function render()
    {
        $totalAgencies = Agency::count();
        $activeAgencies = Agency::where('status', 'active')->count();
        $pendingAgencies = Agency::where('status', 'pending')->count();
        $inactiveAgencies = Agency::where('status', 'inactive')->count();
        $totalAgencyAdmins = User::whereHas('roles', function($query) {
            $query->where('name', 'agency-admin');
        })->count();
        
        $recentAgencies = Agency::with('admin')
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard', compact(
            'totalAgencies',
            'activeAgencies', 
            'pendingAgencies',
            'inactiveAgencies',
            'totalAgencyAdmins',
            'recentAgencies'
        ))->layout('layouts.admin')
          ->title('لوحة تحكم مدير النظام - نظام إدارة وكالات السفر');
    }
}
