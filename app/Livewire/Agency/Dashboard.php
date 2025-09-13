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
    $userId   = Auth::user()->id;
    $isAdmin  = Auth::user()->hasRole('agency-admin');

    // إحصائيات المستخدمين
    if ($isAdmin) {
        $this->totalUsers  = User::where('agency_id', $agencyId)->count();
        $this->activeUsers = User::where('agency_id', $agencyId)->where('is_active', 1)->count();
        $this->onlineUsers = User::where('agency_id', $agencyId)
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '>=', now()->subMinutes(5))
            ->count();
    } else {
        $this->totalUsers  = 1;
        $this->activeUsers = Auth::user()->is_active ? 1 : 0;
        $this->onlineUsers = (Auth::user()->last_activity_at && Auth::user()->last_activity_at >= now()->subMinutes(5)) ? 1 : 0;
    }

    // الخدمات + تحميل أولية للرسوم البيانية
    $this->serviceTypes = ServiceType::where('agency_id', $agencyId)->get();
    $this->selectedServiceType = $this->serviceTypes->first()?->id;
    $this->updateStatsData();

    // المبيعات حسب الخدمة (استبعاد Void والاحتفاظ بالسالب للاسترداد)
    $salesByServiceQuery = Sale::select(
        'service_type_id',
        DB::raw('SUM(usd_sell) as total_sales'),
        DB::raw('SUM(CASE WHEN usd_sell > 0 THEN 1 ELSE 0 END) as operations_count')
    )
    ->where('agency_id', $agencyId)
    ->where('status','!=','Void');

    if (!$isAdmin) $salesByServiceQuery->where('user_id', $userId);

    $this->salesByService = $salesByServiceQuery
        ->groupBy('service_type_id')
        ->with('serviceType')
        ->get()
        ->map(fn($row) => [
            'service_type'     => $row->serviceType?->name ?? '-',
            'total_sales'      => $row->total_sales,
            'operations_count' => $row->operations_count,
        ])->toArray();

    // الهدف الشهري
    $month = now()->startOfMonth()->toDateString();
    $this->monthlyTarget = AgencyTarget::where('agency_id', $agencyId)
        ->where('month', $month)
        ->value('target_amount') ?? 0;

    // المبيعات حسب الموظف (استبعاد Void)
    $salesByEmployeeQuery = Sale::select(
        'user_id',
        DB::raw('SUM(usd_sell) as total_sales'),
        DB::raw('SUM(CASE WHEN usd_sell > 0 THEN 1 ELSE 0 END) as operations_count')
    )
    ->where('agency_id', $agencyId)
    ->where('status','!=','Void')
    ->whereNotNull('user_id');

    if (!$isAdmin) $salesByEmployeeQuery->where('user_id', $userId);

    $salesData = $salesByEmployeeQuery->groupBy('user_id')->get();
    $users     = User::whereIn('id', $salesData->pluck('user_id'))->get()->keyBy('id');

    $this->salesByEmployee = $salesData->map(fn($row) => [
        'employee'         => $users->get($row->user_id)->name ?? "مستخدم غير معروف (ID: {$row->user_id})",
        'total_sales'      => $row->total_sales,
        'operations_count' => $row->operations_count,
        'user_id'          => $row->user_id,
    ])->toArray();

    // المبيعات حسب الفرع (استبعاد Void)
    $branchIds = Agency::where('parent_id', $agencyId)->pluck('id')->toArray();
    $branchIds[] = $agencyId;

    $this->salesByBranch = Sale::select(
        'agency_id',
        DB::raw('SUM(usd_sell) as total_sales'),
        DB::raw('SUM(CASE WHEN usd_sell > 0 THEN 1 ELSE 0 END) as operations_count')
    )
    ->whereIn('agency_id', $branchIds)
    ->where('status','!=','Void')
    ->groupBy('agency_id')
    ->with('agency')
    ->get()
    ->map(fn($row) => [
        'branch'           => $row->agency?->name ?? '-',
        'total_sales'      => $row->total_sales,
        'operations_count' => $row->operations_count,
    ])->toArray();

    // نطاق الشهر الحالي
    $start = now()->startOfMonth();
    $end   = now()->endOfMonth();

    // إعادة الهدف لنفس الشهر
    $this->monthlyTarget = AgencyTarget::where('agency_id', $agencyId)
        ->where('month', $start->toDateString())
        ->value('target_amount') ?? 0;

    // التكاليف (استبعاد Void)
    $monthlyCostQuery = Sale::where('agency_id', $agencyId)->where('status','!=','Void');
    if (!$isAdmin) $monthlyCostQuery->where('user_id', $userId);
    $this->monthlyCost = $monthlyCostQuery
        ->whereBetween('sale_date', [$start, $end])
        ->sum('usd_buy');

    // أرباح الشهر بنفس منطق شاشة المبيعات (تجميع بالمجموعة + التعامل مع Refund)
    $monthRows = Sale::where('agency_id', $agencyId)
        ->when(!$isAdmin, fn($q) => $q->where('user_id', $userId))
        ->where('status','!=','Void')
        ->whereBetween('sale_date', [$start, $end])
        ->withSum('collections', 'amount')
        ->get(['id','usd_sell','amount_paid','sale_profit','sale_group_id','status']);

    $groupedM = $monthRows->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

    $monthProfit = 0.0;
    foreach ($groupedM as $g) {
        $gProfit = (float) $g->sum('sale_profit');

        $hasRefund = $g->contains(function ($row) {
            $st = mb_strtolower((string)($row->status ?? ''));
            return str_contains($st, 'refund') || (float)$row->usd_sell < 0;
        });

        if ($hasRefund) {
            $positiveOnly = (float) $g->filter(fn($row) => (float)$row->sale_profit > 0)
                                      ->sum('sale_profit');
            $monthProfit += max($positiveOnly, 0.0);
        } else {
            $monthProfit += $gProfit;
        }
    }
    $this->monthlyProfit = round($monthProfit, 2);

    // صافي المدفوع + صافي المُحصَّل (نخصم الاسترداد أولاً من التحصيلات ثم من المدفوع)
    [$netPaid, $netCollected] = $this->computeNetPaidAndCollectedForRange(
        $agencyId,
        $start->toDateString(),
        $end->toDateString(),
        $userId,
        $isAdmin,
        null
    );
    $this->monthlyPaid      = $netPaid;
    $this->monthlyCollected = $netCollected;

    // المبيعات المُحققة
    $this->monthlyAchieved  = $netPaid + $netCollected;

    // المؤجَّل بنفس منطق واجهة المبيعات
    $this->monthlyRemaining = $this->computeDeferredForRange(
        $agencyId,
        $start->toDateString(),
        $end->toDateString(),
        $userId,
        $isAdmin
    );
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
        $userId   = Auth::user()->id;
        $isAdmin  = Auth::user()->hasRole('agency-admin');

        if ($this->statsViewType === 'monthly') {
            // آخر 5 أشهر
            $months = collect();
            $now = now()->startOfMonth();
            for ($i = 4; $i >= 0; $i--) {
                $months->push($now->copy()->subMonths($i));
            }

            $final = $months->map(function($date) use ($agencyId, $userId, $isAdmin) {

                $start = $date->copy()->startOfMonth()->toDateString();
                $end   = $date->copy()->endOfMonth()->toDateString();

                // إجمالي (صافي) المبيعات للشهر (استبعاد Void والاحتفاظ بالسالب للاسترداد)
                $totalQuery = Sale::where('agency_id', $agencyId)
                    ->where('status','!=','Void')
                    ->whereBetween('sale_date', [$start, $end]);

                if (!$isAdmin) $totalQuery->where('user_id', $userId);
                if ($this->selectedServiceType) $totalQuery->where('service_type_id', $this->selectedServiceType);
                $total = (float) $totalQuery->sum('usd_sell');

                // المحصّل الصافي
                [$netPaid, $netCollected] = $this->computeNetPaidAndCollectedForRange(
                    $agencyId, $start, $end, $userId, $isAdmin, $this->selectedServiceType
                );
                $realized = $netPaid + $netCollected;

                // غير محصّل
                $pending = max($total - $realized, 0);

                // عدد العمليات الإيجابية فقط (استبعاد Void)
                $countQuery = Sale::where('agency_id', $agencyId)
                    ->whereBetween('sale_date', [$start, $end])
                    ->where('usd_sell', '>', 0)
                    ->where('status', '!=', 'Void');
                if (!$isAdmin) $countQuery->where('user_id', $userId);
                if ($this->selectedServiceType) $countQuery->where('service_type_id', $this->selectedServiceType);

                return [
                    'year'             => $date->year,
                    'month'            => $date->month,
                    'collected_sales'  => round($realized, 2),
                    'pending_sales'    => round($pending, 2),
                    'total_sales'      => round($total, 2), // = collected + pending
                    'operations_count' => (int) $countQuery->count(),
                ];
            });

            $this->salesByMonth = $final->values()->toArray();

            // عدّاد العمليات العام (استبعاد Void وعدّ الإيجابي فقط)
            $countQuery = Sale::query()
                ->where('agency_id', $agencyId)
                ->where('status','!=','Void')
                ->where('usd_sell','>', 0);

            if (!$isAdmin) $countQuery->where('user_id', $userId);
            if ($this->selectedServiceType) $countQuery->where('service_type_id', $this->selectedServiceType);

            $this->totalSalesCount = (int) $countQuery->count();

        } elseif ($this->statsViewType === 'service') {
            // إجمالي المبيعات (صافي) لكل خدمة (استبعاد Void)
            $totals = Sale::select(
                'service_type_id',
                DB::raw('SUM(usd_sell) as total_net_sales'),
                DB::raw('SUM(CASE WHEN usd_sell > 0 THEN 1 ELSE 0 END) as operations_count')
            )
            ->where('agency_id', $agencyId)
            ->where('status','!=','Void');

            if (!$isAdmin) $totals->where('user_id', $userId);

            $totals = $totals->groupBy('service_type_id')->get()->keyBy('service_type_id');

            // أحسب المحصّل/غير المحصّل لكل خدمة (لكامل الفترة)
            $serviceIds = $totals->keys()->all();
            $services   = \App\Models\DynamicListItem::whereIn('id', $serviceIds)->get()->keyBy('id');

            $rows = collect($serviceIds)->map(function($sid) use ($totals, $services, $agencyId, $userId, $isAdmin) {
                [$p, $c] = $this->computeNetPaidAndCollectedForRange(
                    $agencyId, '1900-01-01', now()->endOfDay()->toDateString(),
                    $userId, $isAdmin, $sid
                );
                $realized  = $p + $c;
                $total     = (float) ($totals[$sid]->total_net_sales ?? 0);
                $pending   = max($total - $realized, 0);
                $opsCount  = (int) ($totals[$sid]->operations_count ?? 0);
                $label     = optional($services->get($sid))->label ?? 'غير محدد';

                return [
                    'service_type'     => $label,
                    'collected_sales'  => round($realized, 2),
                    'pending_sales'    => round($pending, 2),
                    'total_sales'      => round($total, 2),
                    'operations_count' => $opsCount,
                ];
            });

            $this->salesByService = $rows->values()->toArray();

        } elseif ($this->statsViewType === 'employee') {
            // قائمة المستخدمين (أو المستخدم الحالي فقط)
            $userIds = $isAdmin
                ? User::where('agency_id', $agencyId)->pluck('id')
                : collect([$userId]);
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            $rows = $userIds->map(function($uid) use ($agencyId, $users) {

                // إجمالي (صافي) مبيعات هذا الموظف (استبعاد Void)
                $total = (float) Sale::where('agency_id', $agencyId)
                    ->where('user_id', $uid)
                    ->where('status','!=','Void')
                    ->sum('usd_sell');

                // المحصّل/غير المحصّل (لكامل الفترة) لهذا الموظف
                [$p, $c] = $this->computeNetPaidAndCollectedForRange(
                    $agencyId, '1900-01-01', now()->endOfDay()->toDateString(),
                    $uid,     // نمرره كـ userId
                    false,    // نغلق isAdmin هنا حتى تُطبق فلترة user_id
                    null
                );
                $realized = $p + $c;
                $pending  = max($total - $realized, 0);

                $count = Sale::where('agency_id', $agencyId)
                    ->where('user_id', $uid)
                    ->where('status','!=','Void')
                    ->where('usd_sell', '>', 0)
                    ->count();

                $u = $users->get($uid);
                return [
                    'employee'         => $u ? $u->name : 'مستخدم غير معروف (ID: '.$uid.')',
                    'collected_sales'  => round($realized, 2),
                    'pending_sales'    => round($pending, 2),
                    'total_sales'      => round($total, 2),
                    'operations_count' => (int) $count,
                    'user_id'          => $uid,
                ];
            });

            $this->salesByMonth = $rows->values()->toArray();

        } elseif ($this->statsViewType === 'branch') {
            // فروع + الفرع الرئيسي
            $mainAgencyId = $agencyId;
            $branchIds = Agency::where('parent_id', $mainAgencyId)->pluck('id')->toArray();
            $branchIds[] = $mainAgencyId;

            $rows = collect($branchIds)->map(function($aid) {

                $total = (float) Sale::where('agency_id', $aid)
                    ->where('status','!=','Void')
                    ->sum('usd_sell');

                // هنا نريد كل مبيعات الفرع دون تقييد user_id -> نمرر isAdmin=true
                [$p, $c] = $this->computeNetPaidAndCollectedForRange(
                    $aid, '1900-01-01', now()->endOfDay()->toDateString(),
                    null,  // بدون فلترة مستخدم
                    true,  // كأننا أدمن داخل الدالة
                    null
                );
                $realized = $p + $c;
                $pending  = max($total - $realized, 0);

                $count = Sale::where('agency_id', $aid)
                    ->where('status','!=','Void')
                    ->where('usd_sell', '>', 0)
                    ->count();

                $agency = Agency::find($aid);
                return [
                    'branch'           => $agency ? $agency->name : '-',
                    'collected_sales'  => round($realized, 2),
                    'pending_sales'    => round($pending, 2),
                    'total_sales'      => round($total, 2),
                    'operations_count' => (int) $count,
                ];
            });

            $this->salesByMonth = $rows->values()->toArray();
        }
    }

    // تحديد نوع لوحة التحكم حسب دور المستخدم
    public function getDashboardTypeProperty()
    {
        $user = Auth::user();
        if ($user->hasRole('agency-admin')) return 'comprehensive';
        if ($user->hasRole('roles-manager')) return 'roles-focused';
        if ($user->hasRole('users-manager')) return 'users-focused';
        if ($user->hasRole('permissions-manager')) return 'permissions-focused';
        if ($user->hasRole('sales-manager')) return 'sales-focused';
        if ($user->hasRole('hr-manager')) return 'hr-focused';
        if ($user->can('users.view')) return 'users-focused';
        if ($user->can('roles.view')) return 'roles-focused';
        if ($user->can('permissions.view')) return 'permissions-focused';
        if ($user->can('service_types.view')) return 'service-types-focused';
        if ($user->can('sales.view')) return 'sales-focused';
        if ($user->can('employees.view')) return 'hr-focused';
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
        $dashboardType = $this->dashboardType;

        return view("livewire.agency.dashboard.{$dashboardType}")
            ->layout('layouts.agency');
    }

    /**
     * حساب صافي المدفوع مباشرة لنطاق زمني على مستوى المجموعات:
     * net = sum( max( sum(amount_paid) per group - refunds_to_customer_in_group, 0 ) )
     */
    private function computeNetDirectPaidForRange(
        int $agencyId,
        string $startDate,
        string $endDate,
        ?int $userId,
        bool $isAdmin
    ): float {
        $q = Sale::query()
            ->where('agency_id', $agencyId)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->with(['collections']);

        if (!$isAdmin && $userId) {
            $q->where('user_id', $userId);
        }

        $rows = $q->get()->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

        $totalNetDirectPaid = 0.0;

        foreach ($rows as $group) {
            $groupAmountPaid = (float) $group->sum('amount_paid');

            // اعتبر Refund + Void + أي صف سالب كاسترداد
            $groupRefundsToCustomer = (float) $group->sum(function ($s) {
                $st = mb_strtolower((string)($s->status ?? ''));
                $isRefundish = str_contains($st,'refund') || $st === 'void' || ((float)$s->usd_sell) < 0;
                return $isRefundish ? abs((float) $s->usd_sell) : 0.0;
            });

            $net = $groupAmountPaid - $groupRefundsToCustomer;

            if ($net > 0) {
                $totalNetDirectPaid += $net;
            }
        }

        return round($totalNetDirectPaid, 2);
    }

    /**
     * حساب المؤجّل (غير المُحصَّل) لنطاق زمني، بنفس منطق واجهة المبيعات:
     * - تجميع حسب sale_group_id
     * - تجاهل المجموعات التي يصبح صافي بيعها <= 0 (ملغاة/مستردة)
     * - المؤجّل لا يمكن أن يكون سالبًا؛ فقط > 0 يُضاف
     */
    private function computeDeferredForRange(
        int $agencyId,
        string $startDate,
        string $endDate,
        ?int $userId,
        bool $isAdmin
    ): float {
        $q = Sale::query()
            ->where('agency_id', $agencyId)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->with(['collections'])
            ->withSum('collections', 'amount');

        if (!$isAdmin && $userId) {
            $q->where('user_id', $userId);
        }

        // نجلب السطور ونجمّعها مثل صفحة المبيعات
        $rows = $q->get();
        $grouped = $rows->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

        $totalAmount   = 0.0; // إجمالي البيع (netSell)
        $totalReceived = 0.0; // ما تم تحصيله فعليًا

        foreach ($grouped as $group) {
            $groupUsdSell = (float) $group->sum('usd_sell');
            if ($groupUsdSell <= 0.0) { continue; } // مجموعة ملغاة/مستردة بالكامل

            $groupAmountPaid  = (float) $group->sum('amount_paid');
            $groupCollections = (float) $group->pluck('collections')->flatten()->sum('amount');

            if (round($groupUsdSell, 2) === 0.00) {
                continue;
            }

            $netSell      = $groupUsdSell;
            $netCollected = $groupAmountPaid + $groupCollections;
            $netRemaining = $netSell - $netCollected;

            if ($netRemaining <= 0) {
                $totalReceived += $netSell;
            } else {
                $totalReceived += $netCollected;
            }

            $totalAmount += $netSell;
        }

        // المؤجّل = إجمالي البيع - المحصل
        return round($totalAmount - $totalReceived, 2);
    }

    private function computeNetPaidAndCollectedForRange(
        int $agencyId,
        string $startDate,
        string $endDate,
        ?int $userId,
        bool $isAdmin,
        ?int $serviceTypeId = null
    ): array {
        $q = Sale::query()
            ->where('agency_id', $agencyId)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->with(['collections']);

        // فلترة المستخدم (تُفعل فقط إذا لم يكن أدمن)
        if (!$isAdmin && $userId) {
            $q->where('user_id', $userId);
        }

        // فلترة نوع الخدمة (اختياري)
        if ($serviceTypeId) {
            $q->where('service_type_id', $serviceTypeId);
        }

        $rows    = $q->get()->groupBy(fn($s) => $s->sale_group_id ?: $s->id);
        $sumPaid = 0.0; // صافي المدفوع مباشرة
        $sumColl = 0.0; // صافي المبالغ المُحصّلة

        foreach ($rows as $group) {
            $groupAmountPaid  = (float) $group->sum('amount_paid');
            $groupCollections = (float) $group->pluck('collections')->flatten()->sum('amount');

            // الاسترداد: Refund + Void + أي صف سالب
            $refundsToCustomer = (float) $group->sum(function ($s) {
                $st = mb_strtolower((string)($s->status ?? ''));
                $isRefundish = str_contains($st, 'refund') || $st === 'void' || ((float)$s->usd_sell) < 0;
                return $isRefundish ? abs((float) $s->usd_sell) : 0.0;
            });

            // نوزّع الاسترداد: من التحصيلات أولاً ثم من المدفوع
            $refund_from_collections = min($refundsToCustomer, $groupCollections);
            $refund_from_paid        = max($refundsToCustomer - $groupCollections, 0);

            $netCollected = max($groupCollections - $refund_from_collections, 0);
            $netPaid      = max($groupAmountPaid - $refund_from_paid, 0);

            $sumColl += $netCollected;
            $sumPaid += $netPaid;
        }

        return [round($sumPaid, 2), round($sumColl, 2)];
    }
}
