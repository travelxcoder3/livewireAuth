<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\User;
use App\Models\Agency;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public function mount()
    {
        if (!Auth::check() || !Auth::user()->agency_id) {
            session()->flash('error', 'ليس لديك صلاحيات للوصول للوحة التحكم.');
            return redirect('/');
        }
    }

    // تحديد نوع لوحة التحكم حسب دور المستخدم
    public function getDashboardTypeProperty()
    {
        $user = Auth::user();
        // أولوية لدور أدمن الوكالة
        if ($user->hasRole('agency-admin')) {
            return 'comprehensive';
        }
        // أولوية لدور مدير الأدوار
        if ($user->hasRole('roles-manager')) {
            return 'roles-focused';
        }
        // أولوية لدور مدير المستخدمين
        if ($user->hasRole('users-manager')) {
            return 'users-focused';
        }
        // أولوية لدور مدير الصلاحيات
        if ($user->hasRole('permissions-manager')) {
            return 'permissions-focused';
        }
        // إذا كان لديه صلاحية users.view فقط
        if ($user->can('users.view')) {
            return 'users-focused';
        }
        // إذا كان لديه صلاحية roles.view فقط
        if ($user->can('roles.view')) {
            return 'roles-focused';
        }
        // إذا كان لديه صلاحية permissions.view فقط
        if ($user->can('permissions.view')) {
            return 'permissions-focused';
        }
        // المستخدم العادي - لوحة تحكم مبسطة
        return 'simple';
    }

    // إحصائيات شاملة (لأدمن الوكالة)
    public function getComprehensiveStatsProperty()
    {
        return [
            'total_users' => $this->totalUsers,
            'active_users' => $this->activeUsers,
            'roles_count' => $this->rolesCount,
            'permissions_count' => $this->permissionsCount,
            'most_used_role' => $this->mostUsedRole,
            'most_used_permission' => $this->mostUsedPermission,
        ];
    }

    // إحصائيات تركز على الأدوار (لمدير الأدوار)
    public function getRolesStatsProperty()
    {
        $roles = \Spatie\Permission\Models\Role::where('agency_id', Auth::user()->agency_id)
            ->withCount('users')
            ->orderByDesc('users_count')
            ->get();

        return [
            'total_roles' => $roles->count(),
            'roles_with_users' => $roles->where('users_count', '>', 0)->count(),
            'empty_roles' => $roles->where('users_count', 0)->count(),
            'top_roles' => $roles->take(5),
            'recent_roles' => $this->recentRoles,
        ];
    }

    // إحصائيات تركز على المستخدمين (لمدير المستخدمين)
    public function getUsersStatsProperty()
    {
        $users = User::where('agency_id', Auth::user()->agency_id)
            ->where('id', '!=', Auth::user()->id)
            ->with('roles')
            ->get();

        $usersByRole = $users->groupBy(function($user) {
            return $user->roles->first()->name ?? 'بدون دور';
        });

        return [
            'total_users' => $users->count(),
            'active_users' => $users->where('is_active', true)->count(),
            'inactive_users' => $users->where('is_active', false)->count(),
            'users_by_role' => $usersByRole,
            'recent_users' => $this->recentUsers,
        ];
    }

    // إحصائيات تركز على الصلاحيات (لمدير الصلاحيات)
    public function getPermissionsStatsProperty()
    {
        $permissions = \Spatie\Permission\Models\Permission::where('agency_id', Auth::user()->agency_id)
            ->withCount('roles')
            ->orderByDesc('roles_count')
            ->get();

        return [
            'total_permissions' => $permissions->count(),
            'used_permissions' => $permissions->where('roles_count', '>', 0)->count(),
            'unused_permissions' => $permissions->where('roles_count', 0)->count(),
            'top_permissions' => $permissions->take(5),
            'recent_permissions' => $this->recentPermissions,
        ];
    }

    // إحصائيات مبسطة (للمستخدم العادي)
    public function getSimpleStatsProperty()
    {
        return [
            'total_users' => $this->totalUsers,
            'active_users' => $this->activeUsers,
            'agency_info' => $this->agencyInfo,
        ];
    }

    public function getTotalUsersProperty()
    {
        return User::where('agency_id', Auth::user()->agency_id)
            ->where('id', '!=', Auth::user()->id)
            ->count();
    }

    public function getActiveUsersProperty()
    {
        return User::where('agency_id', Auth::user()->agency_id)
            ->where('is_active', true)
            ->where('id', '!=', Auth::user()->id)
            ->count();
    }

    public function getRecentUsersProperty()
    {
        return User::where('agency_id', Auth::user()->agency_id)
            ->where('id', '!=', Auth::user()->id)
            ->with('roles')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getAgencyInfoProperty()
    {
        return Auth::user()->agency;
    }

    public function getRolesCountProperty()
    {
        return \Spatie\Permission\Models\Role::where('agency_id', Auth::user()->agency_id)->count();
    }

    public function getPermissionsCountProperty()
    {
        return \Spatie\Permission\Models\Permission::where('agency_id', Auth::user()->agency_id)->count();
    }

    public function getMostUsedRoleProperty()
    {
        return \Spatie\Permission\Models\Role::where('agency_id', Auth::user()->agency_id)
            ->withCount('users')
            ->orderByDesc('users_count')
            ->first();
    }

    public function getMostUsedPermissionProperty()
    {
        return \Spatie\Permission\Models\Permission::where('agency_id', Auth::user()->agency_id)
            ->withCount('roles')
            ->orderByDesc('roles_count')
            ->first();
    }

    public function getRecentRolesProperty()
    {
        return \Spatie\Permission\Models\Role::where('agency_id', Auth::user()->agency_id)
            ->latest()
            ->take(5)
            ->get();
    }

    public function getRecentPermissionsProperty()
    {
        return \Spatie\Permission\Models\Permission::where('agency_id', Auth::user()->agency_id)
            ->latest()
            ->take(5)
            ->get();
    }

    public function render()
    {
        $dashboardType = $this->dashboardType;
        $title = 'لوحة التحكم - ' . Auth::user()->agency->name;
        
        // إضافة نوع لوحة التحكم للعنوان
        switch ($dashboardType) {
            case 'comprehensive':
                $title .= ' (شاملة)';
                break;
            case 'roles-focused':
                $title .= ' (إدارة الأدوار)';
                break;
            case 'users-focused':
                $title .= ' (إدارة المستخدمين)';
                break;
            case 'permissions-focused':
                $title .= ' (إدارة الصلاحيات)';
                break;
            case 'simple':
                $title .= ' (مبسطة)';
                break;
        }

        return view('livewire.agency.dashboard')
            ->layout('layouts.agency')
            ->title($title);
    }
} 