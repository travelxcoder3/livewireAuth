<?php
namespace App\Livewire\Agency;

use App\Models\User;
use App\Models\Sale;
use Livewire\Component;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

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
        $this->customerCommissions = Sale::with(['customer', 'collections'])
            ->whereHas('customer', fn($q) => $q->where('has_commission', true))
            ->whereHas('user', fn($q) => $q->where('agency_id', Auth::user()->agency_id))
            ->whereMonth('sale_date', $this->month)
            ->whereYear('sale_date', $this->year)
            ->get()
            ->map(function ($sale) {
                $collected = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
                $isFullyCollected = $collected >= ($sale->usd_sell ?? 0);

                $dueAmount = $isFullyCollected ? ($sale->commission ?? 0) : 0;

                return [
                    'customer' => $sale->customer?->name ?? 'غير معروف',
                    'amount' => $sale->usd_sell,
                    'profit' => $sale->sale_profit,
                    'status' => $isFullyCollected ? 'تم التحصيل' : 'غير محصل',
                    'commission' => $sale->commission,
                    'date' => $sale->sale_date->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    public function loadEmployeeCommissions() // تغيير اسم الدالة
    {
        $agencyId = Auth::user()->agency_id;
        $users = User::where('agency_id', $agencyId)->get();

        $this->employeeCommissions = $users->map(function ($user) {
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
            $rate = 0.17; // نسبة العمولة ثابتة 17%

            $expectedCommission = max(($totalProfit - $target) * $rate, 0);
            $earnedCommission = max(($collectedProfit - $target) * $rate, 0);

            return [
                'user' => $user->name,
                'target' => $target,
                'rate' => $rate * 100,
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