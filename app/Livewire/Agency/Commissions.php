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
    public $commissionData = [];
    public $customerCommissionData = [];


    public function mount()
    {
        $this->month = now()->format('m');
        $this->year = now()->format('Y');
        $this->loadCommissions();
        $this->loadCustomerCommissions();

    }

    public function loadCustomerCommissions()
    {
        $this->customerCommissionData = Sale::with(['customer', 'collections'])
            ->whereHas('customer', fn($q) => $q->where('has_commission', true))
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
                    'date' => $sale->sale_date,
                ];
            })
            ->toArray();
    }



    public function loadCommissions()
    {
        $agencyId = Auth::user()->agency_id;
        $users = User::where('agency_id', $agencyId)->get();

        $this->commissionData = $users->map(function ($user) {
            $sales = Sale::where('user_id', $user->id)
                ->whereMonth('sale_date', $this->month)
                ->whereYear('sale_date', $this->year)
                ->get();

            $C = $sales->sum('sale_profit'); // الربح الكلي المتوقع
            $D = $sales->filter(function ($sale) {
                $totalRequired = $sale->usd_sell;
                $collected = $sale->amount_paid + $sale->collections->sum('amount');
                return $collected >= $totalRequired;
            })->sum('sale_profit');

            $A = $user->main_target ?? 0;
           // $B = $user->commission_rate / 100;
            $B = 0.17; // نسبة العمولة ثابتة 17%


            $expected = max(($C - $A) * $B, 0);
            $earned   = max(($D - $A) * $B, 0);

            return [
                'user' => $user->name,
                'target' => $A,
                'rate' => $B * 100,
                'total_profit' => $C,
                'collected_profit' => $D,
                'expected_commission' => round($expected, 2),
                'earned_commission' => round($earned, 2),
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.agency.commissions')->layout('layouts.agency');
    }
}
