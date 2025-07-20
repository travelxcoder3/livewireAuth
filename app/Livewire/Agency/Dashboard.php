<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\User;
use App\Models\Agency;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceType;
use ArielMejiaDev\LarapexCharts\LarapexChart;
use App\Charts\SalesChart;

class Dashboard extends Component
{
    public $salesByMonth = [];
    public $serviceTypes = [];
    public $selectedServiceType = null;
    public $statsViewType = 'monthly'; // 'monthly' or 'service'
    public $chartType = 'table'; // table, bar, pie, line
    public $totalSalesCount = 0;
    // المتغيرات الجديدة
    public $salesByService = [];
    public $salesByEmployee = [];
    public $salesByBranch = [];
    public $totalUsers = 0;
    public $activeUsers = 0;
    public $onlineUsers = 0;

    public function mount()
    {
        if (!Auth::check() || !Auth::user()->agency_id) {
            session()->flash('error', 'ليس لديك صلاحيات للوصول للوحة التحكم.');
            return redirect('/');
        }

        $agencyId = Auth::user()->agency_id;
        $this->totalUsers = \App\Models\User::where('agency_id', $agencyId)->count();
        $this->activeUsers = \App\Models\User::where('agency_id', $agencyId)->where('is_active', 1)->count();
        $this->onlineUsers = \App\Models\User::where('agency_id', $agencyId)
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '>=', now()->subMinutes(5))
            ->count();

        // جلب جميع الخدمات للوكالة الحالية
        $this->serviceTypes = ServiceType::where('agency_id', Auth::user()->agency_id)->get();
        $this->selectedServiceType = $this->serviceTypes->first()?->id;
        $this->updateStatsData();

        // تجهيز بيانات المبيعات حسب الخدمة
        $this->salesByService = Sale::select(
            'service_type_id',
            DB::raw('SUM(amount_paid) as total_sales'),
            DB::raw('COUNT(*) as operations_count')
        )
        ->where('agency_id', Auth::user()->agency_id)
        ->groupBy('service_type_id')
        ->with('serviceType')
        ->get()
        ->map(function($row) {
            return [
                'service_type' => $row->serviceType ? $row->serviceType->name : '-',
                'total_sales' => $row->total_sales,
                'operations_count' => $row->operations_count
            ];
        })->toArray();

        // تجهيز بيانات المبيعات حسب الموظف
        $this->salesByEmployee = Sale::select(
            'user_id',
            DB::raw('SUM(amount_paid) as total_sales'),
            DB::raw('COUNT(*) as operations_count')
        )
        ->where('agency_id', Auth::user()->agency_id)
        ->groupBy('user_id')
        ->with('user')
        ->get()
        ->map(function($row) {
            return [
                'employee' => $row->user ? $row->user->name : '-',
                'total_sales' => $row->total_sales,
                'operations_count' => $row->operations_count
            ];
        })->toArray();

        // تجهيز بيانات المبيعات حسب الفرع
        $mainAgencyId = Auth::user()->agency_id;
        $branchIds = Agency::where('parent_id', $mainAgencyId)->pluck('id')->toArray();
        $branchIds[] = $mainAgencyId;
        $this->salesByBranch = Sale::select(
            'agency_id',
            DB::raw('SUM(amount_paid) as total_sales'),
            DB::raw('COUNT(*) as operations_count')
        )
        ->whereIn('agency_id', $branchIds)
        ->groupBy('agency_id')
        ->with('agency')
        ->get()
        ->map(function($row) {
            return [
                'branch' => $row->agency ? $row->agency->name : '-',
                'total_sales' => $row->total_sales,
                'operations_count' => $row->operations_count
            ];
        })->toArray();
    }

    public function updatedSelectedServiceType()
    {
        if ($this->statsViewType === 'monthly') {
            $this->updateStatsData();
        }
    }

    public function updateStatsViewType($type)
    {
        $this->statsViewType = $type;
        $this->updateStatsData();
    }

    public function updateStatsData()
    {
        if ($this->statsViewType === 'monthly') {
            // توليد آخر 5 أشهر (سنة/شهر)
            $months = collect();
            $now = now()->startOfMonth();
            for ($i = 4; $i >= 0; $i--) {
                $date = $now->copy()->subMonths($i);
                $months->push([
                    'year' => $date->year,
                    'month' => $date->month,
                    'total_sales' => 0,
                    'operations_count' => 0,
                ]);
            }

            $query = Sale::select(
                DB::raw('YEAR(sale_date) as year'),
                DB::raw('MONTH(sale_date) as month'),
                DB::raw('SUM(amount_paid) as total_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->where('agency_id', Auth::user()->agency_id);
            if ($this->selectedServiceType) {
                $query->where('service_type_id', $this->selectedServiceType);
            }
            $sales = $query
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // دمج النتائج مع الأشهر (أي شهر ليس فيه مبيعات يبقى صفر)
            $salesMap = $sales->keyBy(function($row) {
                return $row->year . '-' . $row->month;
            });
            $final = $months->map(function($item) use ($salesMap) {
                $key = $item['year'] . '-' . $item['month'];
                if ($salesMap->has($key)) {
                    $row = $salesMap[$key];
                    return [
                        'year' => $row->year,
                        'month' => $row->month,
                        'total_sales' => $row->total_sales,
                        'operations_count' => $row->operations_count,
                    ];
                }
                return $item;
            });
            $this->salesByMonth = $final->values()->toArray();

            // حساب عدد العمليات الفعلية
            $countQuery = Sale::where('agency_id', Auth::user()->agency_id);
            if ($this->selectedServiceType) {
                $countQuery->where('service_type_id', $this->selectedServiceType);
            }
            $this->totalSalesCount = $countQuery->count();

            // تجهيز بيانات الرسم البياني باستخدام LarapexChart
            $labels = $final->map(fn($row) => $row['year'] . '/' . $row['month'])->toArray();
            $data = $final->map(fn($row) => $row['total_sales'])->toArray();
            $this->monthlyChart = (new LarapexChart)
                ->setType('bar')
                ->setTitle('إحصائيات المبيعات حسب الشهر')
                ->setXAxis($labels)
                ->setDataset([
                    [
                        'name' => 'إجمالي المبيعات',
                        'data' => $data
                    ]
                ]);
        } else if ($this->statsViewType === 'service') {
            $this->salesByMonth = Sale::select(
                'service_type_id',
                DB::raw('SUM(amount_paid) as total_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->where('agency_id', Auth::user()->agency_id)
            ->groupBy('service_type_id')
            ->with('serviceType')
            ->get()
            ->map(function($row) {
                return [
                    'service_type' => $row->serviceType ? $row->serviceType->name : '-',
                    'total_sales' => $row->total_sales,
                    'operations_count' => $row->operations_count
                ];
            })->toArray();
        } else if ($this->statsViewType === 'employee') {
            $this->salesByMonth = Sale::select(
                'user_id',
                DB::raw('SUM(amount_paid) as total_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->where('agency_id', Auth::user()->agency_id)
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(function($row) {
                return [
                    'employee' => $row->user ? $row->user->name : '-',
                    'total_sales' => $row->total_sales,
                    'operations_count' => $row->operations_count
                ];
            })->toArray();
        } else if ($this->statsViewType === 'branch') {
            // جلب جميع الفروع (agencies التي parent_id = id الوكالة الحالية)
            $mainAgencyId = Auth::user()->agency_id;
            $branchIds = \App\Models\Agency::where('parent_id', $mainAgencyId)->pluck('id')->toArray();
            // أضف الوكالة الرئيسية دائماً
            $branchIds[] = $mainAgencyId;
            $this->salesByMonth = Sale::select(
                'agency_id',
                DB::raw('SUM(amount_paid) as total_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->whereIn('agency_id', $branchIds)
            ->groupBy('agency_id')
            ->with('agency')
            ->get()
            ->map(function($row) {
                return [
                    'branch' => $row->agency ? $row->agency->name : '-',
                    'total_sales' => $row->total_sales,
                    'operations_count' => $row->operations_count
                ];
            })->toArray();
        }
    }

    public function updatedChartType()
    {
        $this->dispatch('refreshChart');
    }

    public function setChartType($type)
    {
        $this->chartType = $type;
        $this->dispatch('refreshChart');
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
        // أولوية لدور مدير المبيعات
        if ($user->hasRole('sales-manager')) {
            return 'sales-focused';
        }
        // أولوية لدور مدير الموارد البشرية
        if ($user->hasRole('hr-manager')) {
            return 'hr-focused';
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
        // إذا كان لديه صلاحية service_types.view فقط
        if ($user->can('service_types.view')) {
            return 'service-types-focused';
        }
        // إذا كان لديه صلاحية sales.view فقط
        if ($user->can('sales.view')) {
            return 'sales-focused';
        }
        // إذا كان لديه صلاحية employees.view فقط
        if ($user->can('employees.view')) {
            return 'hr-focused';
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
        $permissions = \Spatie\Permission\Models\Permission::whereNull('agency_id')
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
        return \Spatie\Permission\Models\Permission::whereNull('agency_id')->count();
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
        return \Spatie\Permission\Models\Permission::whereNull('agency_id')
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
        return \Spatie\Permission\Models\Permission::whereNull('agency_id')
            ->latest()
            ->take(5)
            ->get();
    }

    public function render()
    {
        // تجهيز بيانات المبيعات الشهرية (نفس ما يُستخدم للجدول)
        $sales = collect($this->salesByMonth);
        $labels = $sales->map(function($row) {
            return ($row['year'] ?? '-') . '/' . str_pad($row['month'] ?? '', 2, '0', STR_PAD_LEFT);
        })->toArray();
        $data = $sales->map(function($row) {
            return $row['operations_count'] ?? 0;
        })->toArray();

        return view('livewire.agency.dashboard.comprehensive', [
            'chartLabels' => $labels,
            'chartData' => $data,
        ])->layout('layouts.agency');
    }
} 