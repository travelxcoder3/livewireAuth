<?php
namespace App\Livewire\Agency;

use App\Models\User;
use App\Models\Sale;
use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Models\CommissionProfile;
use App\Models\CommissionEmployeeRateOverride;
class Commissions extends Component
{
    public $month;
    public $year;
    public $employeeCommissions = []; // تغيير الاسم من commissionData
    public $customerCommissions = []; // تغيير الاسم من customerCommissionData

    public function mount()
    {
        $this->month = now()->format('m');
        $this->year = now()->format('Y');
        $this->loadEmployeeCommissions(); // تغيير اسم الدالة
        $this->loadCustomerCommissions();
    }

    public function loadCustomerCommissions()
{
    $agencyId = Auth::user()->agency_id;

    $this->customerCommissions = Sale::with(['customer', 'collections'])
    ->whereHas('customer', function ($q) use ($agencyId) {
        $q->where('has_commission', true)
          ->where('agency_id', $agencyId);
    })
    ->whereMonth('sale_date', $this->month)
    ->whereYear('sale_date', $this->year)
    ->get()
    ->groupBy('sale_group_id')  // ✅ لتجميع المبيعات المكررة
    ->map(function ($sales) {
        $first = $sales->first();

        // ✅ إذا إحدى المبيعات عمولتها = 0 وحالتها "Refund-Full"، نحسب 0
        $hasFullRefund = $sales->contains(function ($s) {
            return $s->status === 'Refund-Full' && floatval($s->commission) == 0;
        });

        $commission = $hasFullRefund
            ? 0
            : $sales->first()->commission; // ✅ نأخذ فقط أول عمولة إن لم يكن هناك استرداد كلي

        $collected = ($first->amount_paid ?? 0) + $first->collections->sum('amount');
        $isFullyCollected = $collected >= ($first->usd_sell ?? 0);

        return [
            'customer' => $first->customer?->name ?? 'غير معروف',
            'amount' => $first->usd_sell,
            'profit' => $first->sale_profit,
            'status' => $isFullyCollected ? 'تم التحصيل' : 'غير محصل',
            'commission' => $commission,
            'date' => \Carbon\Carbon::parse($first->sale_date)->format('Y-m-d'),
        ];
    })
    ->values() // تحويل Collection إلى Array مرتبة
    ->toArray();

}

    public function loadEmployeeCommissions() // تغيير اسم الدالة
    {
        $agencyId = Auth::user()->agency_id;

// ...
$users = User::where('agency_id', $agencyId)->get();

// حمّل بروفايل الوكالة ونِسب الاستثناءات مرّة واحدة
$profile = CommissionProfile::where('agency_id', $agencyId)
            ->where('is_active', true)->first();

$overrideMap = $profile
    ? CommissionEmployeeRateOverride::where('profile_id', $profile->id)->pluck('rate','user_id')
    : collect();

$this->employeeCommissions = $users->map(function ($user) use ($profile, $overrideMap) {
    $sales = Sale::where('user_id', $user->id)
        ->whereMonth('sale_date', $this->month)
        ->whereYear('sale_date', $this->year)
        ->get();

    $totalProfit = $sales->sum('sale_profit');
    $collectedProfit = $sales->filter(function ($sale) {
        $totalRequired = $sale->usd_sell;
        $collected = $sale->amount_paid + $sale->collections->sum('amount');
        return $collected >= $totalRequired;
    })->sum('sale_profit');

    $target = $user->main_target ?? 0;

    // النسبة من قاعدة البيانات: استثناء المستخدم ثم النسبة العامة للبروفايل
    $effectiveRatePct = $profile
        ? ($overrideMap[$user->id] ?? (float) ($profile->employee_rate ?? 0))
        : 0;
    $rate = ((float)$effectiveRatePct) / 100.0;

    $expectedCommission = max(($totalProfit - $target) * $rate, 0);
    $earnedCommission = max(($collectedProfit - $target) * $rate, 0);

    return [
        'user' => $user->name,
        'target' => $target,
        'rate' => $effectiveRatePct, // أعرضها كنسبة %
        'total_profit' => $totalProfit,
        'collected_profit' => $collectedProfit,
        'expected_commission' => round($expectedCommission, 2),
        'earned_commission' => round($earnedCommission, 2),
    ];
})->toArray();

    }

    public function render()
    {
        return view('livewire.agency.commissions')->layout('layouts.agency');
    }
}