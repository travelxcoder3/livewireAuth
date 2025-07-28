<?php

namespace App\Livewire\Agency;

use Livewire\Component;
use App\Models\User;
use App\Models\Agency;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceType;
use App\Models\AgencyTarget;

class Dashboard extends Component
{
    public $salesByMonth = [];
    public $serviceTypes = [];
    public $selectedServiceType = null;
    public $statsViewType = 'monthly'; // 'monthly' or 'service'
    public $totalSalesCount = 0;
    // المتغيرات الجديدة
    public $salesByService = [];
    public $salesByEmployee = [];
    public $salesByBranch = [];
    public $totalUsers = 0;
    public $activeUsers = 0;
    public $onlineUsers = 0;
    public $monthlyTarget = 0;
    public $monthlyAchieved = 0;
    public $monthlyProfit = 0;
    public $monthlyCost = 0;
    public $monthlyPaid = 0;
public $monthlyCollected = 0;
public $monthlyRemaining = 0;

    public function mount()
    {

        if (!Auth::check() || !Auth::user()->agency_id) {
            session()->flash('error', 'ليس لديك صلاحيات للوصول للوحة التحكم.');
            return redirect('/');
        }

        $agencyId = Auth::user()->agency_id;
        $userId = Auth::user()->id;
        $isAdmin = Auth::user()->hasRole('agency-admin');
        
        // إحصائيات المستخدمين حسب الدور
        if ($isAdmin) {
            // إحصائيات جميع موظفي الوكالة للأدمن
            $this->totalUsers = \App\Models\User::where('agency_id', $agencyId)->count();
            $this->activeUsers = \App\Models\User::where('agency_id', $agencyId)->where('is_active', 1)->count();
            $this->onlineUsers = \App\Models\User::where('agency_id', $agencyId)
                ->whereNotNull('last_activity_at')
                ->where('last_activity_at', '>=', now()->subMinutes(5))
                ->count();
        } else {
            // إحصائيات المستخدم الحالي فقط للموظفين العاديين
            $this->totalUsers = 1;
            $this->activeUsers = Auth::user()->is_active ? 1 : 0;
            $this->onlineUsers = (Auth::user()->last_activity_at && Auth::user()->last_activity_at >= now()->subMinutes(5)) ? 1 : 0;
        }

        // جلب جميع الخدمات للوكالة الحالية
        $this->serviceTypes = ServiceType::where('agency_id', Auth::user()->agency_id)->get();
        $this->selectedServiceType = $this->serviceTypes->first()?->id;
        $this->updateStatsData();

        // تجهيز بيانات المبيعات حسب الخدمة
        $salesByServiceQuery = Sale::select(
            'service_type_id',
            DB::raw('SUM(amount_paid) as total_sales'),
            DB::raw('COUNT(*) as operations_count')
        )
        ->where('agency_id', Auth::user()->agency_id);
        
        // إضافة فلتر المستخدم فقط إذا لم يكن أدمن
        if (!$isAdmin) {
            $salesByServiceQuery->where('user_id', $userId);
        }
        
        $this->salesByService = $salesByServiceQuery
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
        $month = now()->startOfMonth()->toDateString();

// 1. جلب الهدف البيعي من جدول agency_targets
$this->monthlyTarget = AgencyTarget::where('agency_id', $agencyId)
    ->where('month', $month)
    ->value('target_amount') ?? 0;

// 2. حساب المبيعات المحققة فعليًا لهذا الشهر
$monthlyAchievedQuery = Sale::where('agency_id', $agencyId);
if (!$isAdmin) {
    $monthlyAchievedQuery->where('user_id', $userId);
}
$this->monthlyAchieved = $monthlyAchievedQuery
    ->whereBetween('sale_date', [now()->startOfMonth(), now()->endOfMonth()])
    ->sum('amount_paid');
        // تجهيز بيانات المبيعات حسب الموظف
        $salesByEmployeeQuery = Sale::select(
            'user_id',
            DB::raw('SUM(COALESCE(usd_sell, amount_paid, 0)) as total_sales'),
            DB::raw('COUNT(*) as operations_count')
        )
        ->where('agency_id', Auth::user()->agency_id)
        ->whereNotNull('user_id'); // تأكد من وجود user_id
        
        // إضافة فلتر المستخدم فقط إذا لم يكن أدمن
        if (!$isAdmin) {
            $salesByEmployeeQuery->where('user_id', $userId);
        }
        
        $salesData = $salesByEmployeeQuery
        ->groupBy('user_id')
        ->get();
        
        // جلب بيانات المستخدمين بشكل منفصل
        $userIds = $salesData->pluck('user_id')->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        
        $this->salesByEmployee = $salesData->map(function($row) use ($users) {
            $user = $users->get($row->user_id);
            return [
                'employee' => $user ? $user->name : 'مستخدم غير معروف (ID: ' . $row->user_id . ')',
                'total_sales' => $row->total_sales,
                'operations_count' => $row->operations_count,
                'user_id' => $row->user_id
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
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        
                // 1. الهدف
                $this->monthlyTarget = AgencyTarget::where('agency_id', $agencyId)
                    ->where('month', $start->toDateString())
                    ->value('target_amount') ?? 0;
        
                // 2. المبيعات المحققة
                $monthlyAchievedQuery2 = Sale::where('agency_id', $agencyId);
                if (!$isAdmin) {
                    $monthlyAchievedQuery2->where('user_id', $userId);
                }
                $paid = Sale::where('agency_id', $agencyId)
    ->when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
    ->whereBetween('sale_date', [$start, $end])
    ->sum('amount_paid');

$collected = DB::table('collections')
    ->join('sales', 'collections.sale_id', '=', 'sales.id')
    ->where('sales.agency_id', $agencyId)
    ->when(!$isAdmin, fn($q) => $q->where('sales.user_id', $userId))
    ->whereBetween('sales.sale_date', [$start, $end])
    ->sum('collections.amount');

$totalUsdSell = Sale::where('agency_id', $agencyId)
    ->when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
    ->whereBetween('sale_date', [$start, $end])
    ->sum('usd_sell');

$this->monthlyAchieved = $totalUsdSell;

        
                // 3. التكاليف (شراء)
                $monthlyCostQuery = Sale::where('agency_id', $agencyId);
                if (!$isAdmin) {
                    $monthlyCostQuery->where('user_id', $userId);
                }
                $this->monthlyCost = $monthlyCostQuery
                    ->whereBetween('sale_date', [$start, $end])
                    ->sum('usd_buy');
        
            // 4. الأرباح = مجموع (سعر البيع - سعر الشراء) لكل عملية
                $this->monthlyProfit = Sale::where('agency_id', $agencyId)
                ->when(!$isAdmin, function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->whereBetween('sale_date', [$start, $end])
                ->sum(\DB::raw('usd_sell - usd_buy'));
                $this->monthlyPaid = $paid;
                $this->monthlyCollected = $collected;
                $this->monthlyRemaining = $totalUsdSell - ($paid + $collected);
                
// أو يمكنك حسابها كالتالي إذا كنت تريد استخدام القيم المجمعة سابقاً:
// $this->monthlyProfit = $this->monthlyAchieved - $this->monthlyCost; // لكن هذا يعتمد على أن amount_paid = usd_sell
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
        $agencyId = Auth::user()->agency_id;
        $userId = Auth::user()->id;
        $isAdmin = Auth::user()->hasRole('agency-admin');
        if ($this->statsViewType === 'monthly') {
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
            // استعلام مبيعات كل شهر
            $sales = Sale::select(
                DB::raw('YEAR(sale_date) as year'),
                DB::raw('MONTH(sale_date) as month'),
                DB::raw('SUM(amount_paid) as direct_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->where('agency_id', $agencyId);
            if (!$isAdmin) {
                $sales->where('user_id', $userId);
            }
            if ($this->selectedServiceType) {
                $sales->where('service_type_id', $this->selectedServiceType);
            }
            $sales = $sales->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();
            // استعلام مجموع المحصلات لكل شهر
            $collections = DB::table('collections')
                ->join('sales', 'collections.sale_id', '=', 'sales.id')
                ->select(
                    DB::raw('YEAR(sales.sale_date) as year'),
                    DB::raw('MONTH(sales.sale_date) as month'),
                    DB::raw('SUM(collections.amount) as collected_sales')
                )
                ->where('sales.agency_id', $agencyId);
            if (!$isAdmin) {
                $collections->where('sales.user_id', $userId);
            }
            if ($this->selectedServiceType) {
                $collections->where('sales.service_type_id', $this->selectedServiceType);
            }
            $collections = $collections->groupBy('year', 'month')->get();
            $collectionsMap = $collections->keyBy(function($row) {
                return $row->year . '-' . $row->month;
            });
            $salesMap = $sales->keyBy(function($row) {
                return $row->year . '-' . $row->month;
            });
            $final = $months->map(function($item) use ($salesMap, $collectionsMap) {
                $key = $item['year'] . '-' . $item['month'];
                $direct_sales = $salesMap[$key]->direct_sales ?? 0;
                $collected_sales = $collectionsMap[$key]->collected_sales ?? 0;
                $operations_count = $salesMap[$key]->operations_count ?? 0;
                    return [
                    'year' => $item['year'],
                    'month' => $item['month'],
                    'total_sales' => $direct_sales + $collected_sales,
                    'operations_count' => $operations_count,
                    ];
            });
            $this->salesByMonth = $final->values()->toArray();
            $countQuery = Sale::where('agency_id', $agencyId)
                ->where('user_id', $userId);
            if ($this->selectedServiceType) {
                $countQuery->where('service_type_id', $this->selectedServiceType);
            }
            $this->totalSalesCount = $countQuery->count();
        } else if ($this->statsViewType === 'service') {
            $sales = Sale::select(
                'service_type_id',
                DB::raw('SUM(amount_paid) as direct_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->where('agency_id', $agencyId);
            if (!$isAdmin) {
                $sales->where('user_id', $userId);
            }
            $sales = $sales->groupBy('service_type_id')->get();
            $collections = DB::table('collections')
                ->join('sales', 'collections.sale_id', '=', 'sales.id')
                ->select(
                    'sales.service_type_id',
                    DB::raw('SUM(collections.amount) as collected_sales')
                )
                ->where('sales.agency_id', $agencyId);
            if (!$isAdmin) {
                $collections->where('sales.user_id', $userId);
            }
            $collections = $collections->groupBy('sales.service_type_id')->get();
            $collectionsMap = $collections->keyBy('service_type_id');
            $salesMap = $sales->keyBy('service_type_id');
            $this->salesByService = $sales->map(function($row) use ($collectionsMap) {
                $collected_sales = $collectionsMap[$row->service_type_id]->collected_sales ?? 0;
                $total_sales = ($row->direct_sales ?? 0) + $collected_sales;
                return [
                    'service_type' => $row->service ? $row->service->label : 'غير محدد',
                    'total_sales' => $total_sales,
                    'operations_count' => $row->operations_count
                ];
            })->toArray();
        } else if ($this->statsViewType === 'employee') {
            $sales = Sale::select(
                'user_id',
                DB::raw('SUM(amount_paid) as direct_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->where('agency_id', $agencyId)
            ->whereNotNull('user_id');
            if (!$isAdmin) {
                $sales->where('user_id', $userId);
            }
            $sales = $sales->groupBy('user_id')->get();
            $collections = DB::table('collections')
                ->join('sales', 'collections.sale_id', '=', 'sales.id')
                ->select(
                    'sales.user_id',
                    DB::raw('SUM(collections.amount) as collected_sales')
                )
                ->where('sales.agency_id', $agencyId)
                ->whereNotNull('sales.user_id');
            if (!$isAdmin) {
                $collections->where('sales.user_id', $userId);
            }
            $collections = $collections->groupBy('sales.user_id')->get();
            $collectionsMap = $collections->keyBy('user_id');
            $salesMap = $sales->keyBy('user_id');
            $userIds = $sales->pluck('user_id')->toArray();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            $this->salesByMonth = $sales->map(function($row) use ($collectionsMap, $users) {
                $collected_sales = $collectionsMap[$row->user_id]->collected_sales ?? 0;
                $total_sales = ($row->direct_sales ?? 0) + $collected_sales;
                $user = $users->get($row->user_id);
                return [
                    'employee' => $user ? $user->name : 'مستخدم غير معروف (ID: ' . $row->user_id . ')',
                    'total_sales' => $total_sales,
                    'operations_count' => $row->operations_count,
                    'user_id' => $row->user_id
                ];
            })->toArray();
        } else if ($this->statsViewType === 'branch') {
            $mainAgencyId = $agencyId;
            $branchIds = \App\Models\Agency::where('parent_id', $mainAgencyId)->pluck('id')->toArray();
            $branchIds[] = $mainAgencyId;
            $sales = Sale::select(
                'agency_id',
                DB::raw('SUM(amount_paid) as direct_sales'),
                DB::raw('COUNT(*) as operations_count')
            )
            ->whereIn('agency_id', $branchIds)
            ->groupBy('agency_id')->get();
            $collections = DB::table('collections')
                ->join('sales', 'collections.sale_id', '=', 'sales.id')
                ->select(
                    'sales.agency_id',
                    DB::raw('SUM(collections.amount) as collected_sales')
                )
                ->whereIn('sales.agency_id', $branchIds)
                ->groupBy('sales.agency_id')->get();
            $collectionsMap = $collections->keyBy('agency_id');
            $salesMap = $sales->keyBy('agency_id');
            $this->salesByMonth = $sales->map(function($row) use ($collectionsMap) {
                $collected_sales = $collectionsMap[$row->agency_id]->collected_sales ?? 0;
                $total_sales = ($row->direct_sales ?? 0) + $collected_sales;
                return [
                    'branch' => $row->agency ? $row->agency->name : '-',
                    'total_sales' => $total_sales,
                    'operations_count' => $row->operations_count
                ];
            })->toArray();
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
        return view('livewire.agency.dashboard.comprehensive')
            ->layout('layouts.agency');
    }
} 