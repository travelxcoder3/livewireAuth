<?php

namespace App\Livewire\Agency\Audit;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\DynamicListItem as ServiceType;
use App\Models\CommissionProfile;
use App\Models\EmployeeMonthlyTarget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Accounts extends Component
{
    use WithPagination;

    private function agencyId(): int
    {
        return Auth::user()->agency_id;
    }

    // فلاتر
    public $date_from, $date_to;
    public $group_by = 'none'; // service_type|customer|provider|employee|none
    public $service_type_id = null;
    public $customer_id = null;
    public $provider_id = null;
    public $employee_id = null;

    // تبويب (مستقبلاً)
    public $tab = 'summary';

    // خيارات السلكت (للكومبوننت)
    public array $serviceTypeOptions = [];
    public array $customerOptions    = [];
    public array $providerOptions    = [];

    // لعرض النص المختار داخل الكومبوننت
    public ?string $serviceTypeLabel = null;
    public ?string $customerLabel    = null;
    public ?string $providerLabel    = null;

    protected $queryString = [
        'date_from','date_to','group_by','service_type_id','customer_id','provider_id','employee_id','tab'
    ];

    public function mount()
    {
        $this->date_from ??= now()->startOfMonth()->toDateString();
        $this->date_to   ??= now()->endOfDay()->toDateString();

$aid = $this->agencyId();
$this->serviceTypeOptions = ServiceType::whereHas('list', fn($q)=>$q->where('name','قائمة الخدمات'))
    ->where(function($q) use ($aid){
        $q->where('created_by_agency', $aid)->orWhereNull('created_by_agency');
    })
    ->orderBy('label')->pluck('label','id')->toArray();





        $this->customerOptions = Customer::where('agency_id', $this->agencyId())
            ->orderBy('name')->pluck('name','id')->toArray();

        $this->providerOptions = Provider::where('agency_id', $this->agencyId())
            ->orderBy('name')->pluck('name','id')->toArray();


        // تسميات مختارة إن كانت الفلاتر فيها قيم
        $this->serviceTypeLabel = $this->service_type_id ? ($this->serviceTypeOptions[$this->service_type_id] ?? null) : null;
        $this->customerLabel    = $this->customer_id     ? ($this->customerOptions[$this->customer_id]       ?? null) : null;
        $this->providerLabel    = $this->provider_id     ? ($this->providerOptions[$this->provider_id]       ?? null) : null;
    }

    /** إعادة تعيين الفلاتر للوضع المبدئي */
    public function resetFilters()
    {
        $this->date_from       = now()->startOfMonth()->toDateString();
        $this->date_to         = now()->endOfDay()->toDateString();
        $this->group_by        = 'none';
        $this->service_type_id = null;
        $this->customer_id     = null;
        $this->provider_id     = null;
        $this->employee_id     = null;

        $this->serviceTypeLabel = null;
        $this->customerLabel    = null;
        $this->providerLabel    = null;

        $this->resetPage();
    }

    // نطاق التاريخ لسجلات المبيعات (sale_date)
    protected function salesDateRange($q) {
        return $q->whereBetween('sale_date', [
            Carbon::parse($this->date_from)->startOfDay(),
            Carbon::parse($this->date_to)->endOfDay(),
        ]);
    }

    protected function salesBase()
{
    return $this->salesDateRange(Sale::query())
        ->where('agency_id', $this->agencyId())
        ->where('status', '!=', 'Void') // إضافة هذا الشرط لتصفية العمليات الملغاة
        ->when($this->service_type_id, fn($q) => $q->where('service_type_id', $this->service_type_id))
        ->when($this->customer_id, fn($q) => $q->where('customer_id', $this->customer_id))
        ->when($this->provider_id, fn($q) => $q->where('provider_id', $this->provider_id))
        ->when($this->employee_id, fn($q) => $q->where('user_id', $this->employee_id));
}


    // KPIs
    public function kpis()
    {
        $salesBase = $this->salesBase();

        $sales        = (clone $salesBase)->sum('usd_sell');
        $costs        = (clone $salesBase)->sum('usd_buy');
        $commissions  = (clone $salesBase)->sum('commission');
        $net_profit   = (clone $salesBase)->sum('sale_profit');

        $refunds = (clone $salesBase)
            ->whereIn('status', ['Refund-Full','Refund-Partial'])
            ->where('usd_sell','<',0)
            ->sum(DB::raw('ABS(usd_sell)'));

        // التحصيلات (انتبه لتأهيل created_at باسم الجدول لتفادي التعارض)
         $collections = DB::table('collections')
        ->join('sales', 'collections.sale_id', '=', 'sales.id')
        ->where('sales.agency_id', $this->agencyId())
        ->where('sales.status', '!=', 'Void') // تصفية العمليات الملغاة
        ->when($this->service_type_id, fn($q) => $q->where('sales.service_type_id', $this->service_type_id))
        ->when($this->customer_id, fn($q) => $q->where('sales.customer_id', $this->customer_id))
        ->when($this->provider_id, fn($q) => $q->where('sales.provider_id', $this->provider_id))
        ->when($this->employee_id, fn($q) => $q->where('sales.user_id', $this->employee_id))
        ->whereBetween('collections.created_at', [
            Carbon::parse($this->date_from)->startOfDay(),
            Carbon::parse($this->date_to)->endOfDay(),
        ])
        ->sum('collections.amount');


        $positiveSales = (clone $salesBase)->where('usd_sell','>',0)->sum('usd_sell');
        $amountPaid    = (clone $salesBase)->where('usd_sell','>',0)->sum('amount_paid');
        $customer_due  = max(0, ($positiveSales - $amountPaid - $collections) - $refunds);

// عمولة الموظف المتوقعة متأثرة بكل فلاتر الصفحة
$ref = now();
if (!empty($this->date_from) && !empty($this->date_to)) {
    $s = Carbon::parse($this->date_from);
    $e = Carbon::parse($this->date_to);
    if ($s->isSameMonth($e)) { $ref = $s; }
}
$yr = (int)$ref->year;
$mo = (int)$ref->month;

// نطاق الموظفين: المفلتر محددًا أو كل موظفي الوكالة
$empIds = $this->employee_id
    ? [(int)$this->employee_id]
    : DB::table('users')->where('agency_id', $this->agencyId())->pluck('id')->all();

$employeeComms = 0.0;
foreach ($empIds as $uid) {
    // الهدف الشهري
    $target = (float)(
        EmployeeMonthlyTarget::where('user_id',$uid)->where('year',$yr)->where('month',$mo)->value('main_target')
        ?? DB::table('users')->where('id',$uid)->value('main_target')
        ?? 0
    );

    // نسبة العمولة
    $override = EmployeeMonthlyTarget::where('user_id',$uid)->where('year',$yr)->where('month',$mo)->value('override_rate');
    $ratePct = ($override !== null)
        ? (float)$override
        : (float)(CommissionProfile::where('agency_id',$this->agencyId())->where('is_active',true)->value('employee_rate') ?? 0);
    $rate = $ratePct / 100.0;

    // نفس فلاتر الصفحة + قيد الشهر
    $rows = (clone $salesBase)->where('user_id',$uid)
        ->whereYear('sale_date',$yr)->whereMonth('sale_date',$mo)
        ->get(['id','usd_sell','sale_profit','sale_group_id','status']);

    if ($rows->isEmpty()) continue;

    // معالجة مجموعات الاسترداد مثل صفحة المبيعات
    $groups = $rows->groupBy(fn($s) => $s->sale_group_id ?: $s->id);
    $monthProfit = 0.0;
    foreach ($groups as $g) {
        $gProfit = (float)$g->sum('sale_profit');
        $hasRefund = $g->contains(fn($row) =>
            str_contains(mb_strtolower((string)($row->status ?? '')),'refund')
            || (float)$row->usd_sell < 0
        );
        $monthProfit += $hasRefund
            ? max((float)$g->where('sale_profit','>',0)->sum('sale_profit'), 0.0)
            : $gProfit;
    }

    $employeeComms += max(($monthProfit - $target) * $rate, 0);
}


        return compact('sales','costs','commissions','employeeComms','net_profit','refunds','collections','customer_due');
    }

    // تعريف التجميع
    protected function groupColumn()
    {
        return match ($this->group_by) {
            'customer'    => ['column'=>'customer_id',    'label'=>'العميل'],
            'provider'    => ['column'=>'provider_id',    'label'=>'المزوّد'],
            'employee'    => ['column'=>'user_id',        'label'=>'الموظف'],
            'service_type'=> ['column'=>'service_type_id','label'=>'الخدمة'],
            default       => null,
        };
    }

    public function querySalesGrouped()
{
    $grp = $this->groupColumn();
    $q = $this->salesBase();

    if ($grp) {
        return $q->selectRaw("{$grp['column']} as key_id,
                            COUNT(*) as row_count,
                            SUM(usd_sell) as total,
                            SUM(usd_buy)  as cost,
                            SUM(commission) as commission,
                            SUM(usd_sell - usd_buy) as net_profit")
            ->groupBy($grp['column'])
            ->paginate(10);
    }

    return $q->with(['service:id,label', 'customer:id,name', 'provider:id,name'])
        ->selectRaw("id, sale_date, service_type_id, customer_id, provider_id,
                    usd_sell as total, usd_buy as cost, commission,
                    (usd_sell - usd_buy) as net_profit")
        ->orderByDesc('sale_date')->orderByDesc('id')
        ->paginate(10);
}


    public function render()
    {
        $kpis = $this->kpis();
        $salesGrouped = $this->querySalesGrouped();

        // تحويل مفاتيح التجميع إلى أسماء لعرضها في الواجهة
        $keyLabels = [];
        switch ($this->group_by) {
           case 'service_type':
$ids = collect($salesGrouped->items())->pluck('key_id')->filter()->unique();
$aid = $this->agencyId();
$keyLabels = ServiceType::whereIn('id', $ids)
    ->whereHas('list', fn($q)=>$q->where('name','قائمة الخدمات'))
    ->where(function($q) use ($aid){
        $q->where('created_by_agency', $aid)->orWhereNull('created_by_agency');
    })
    ->pluck('label','id')->toArray();



            break;
        case 'customer':
            $keyLabels = Customer::whereIn('id', collect($salesGrouped->items())->pluck('key_id')->filter()->unique())
                ->where('agency_id', $this->agencyId())
                ->pluck('name','id')->toArray();
            break;
        case 'provider':
            $keyLabels = Provider::whereIn('id', collect($salesGrouped->items())->pluck('key_id')->filter()->unique())
                ->where('agency_id', $this->agencyId())
                ->pluck('name','id')->toArray();
            break;
        case 'employee':
            $userMap = DB::table('users')
                ->whereIn('id', collect($salesGrouped->items())->pluck('key_id')->filter()->unique())
                ->where('agency_id', $this->agencyId())
                ->pluck('name','id')->toArray();
            $keyLabels = $userMap;
            break;

            default:
                $keyLabels = [];
        }

        // تحدّث تسميات السلكت إذا تغيّر الاختيار
        $this->serviceTypeLabel = $this->service_type_id ? ($this->serviceTypeOptions[$this->service_type_id] ?? null) : null;
        $this->customerLabel    = $this->customer_id     ? ($this->customerOptions[$this->customer_id]       ?? null) : null;
        $this->providerLabel    = $this->provider_id     ? ($this->providerOptions[$this->provider_id]       ?? null) : null;

       if ($this->group_by !== 'none') {
            $salesGrouped->getCollection()->transform(function ($row) use ($keyLabels) {
                $row->key_label = $keyLabels[$row->key_id] ?? $row->key_id;
                return $row;
            });
        }

        return view('livewire.agency.audit.accounts', compact('kpis','salesGrouped','keyLabels'))
            ->layout('layouts.agency');
    }
}
