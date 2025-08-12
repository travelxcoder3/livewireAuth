<?php
namespace App\Livewire\Agency\Reports;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Collection;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.agency')]
class CustomerAccountDetails extends Component
{
    public $customerId;
    public $customer;
    public $sales = [];
    public $collections = [];
    public $availableBalanceToPayOthers = 0;

    public function mount($id)
    {
        $this->customerId = $id;
        $this->customer = Customer::where('agency_id', Auth::user()->agency_id)
            ->findOrFail($id);

        $this->sales = Sale::where('customer_id', $id)
            ->where('agency_id', Auth::user()->agency_id)
            ->get();

        $this->collections = Collection::whereHas('sale', fn($q) => $q->where('customer_id', $id))
            ->where('agency_id', Auth::user()->agency_id)
            ->get();

        $this->calculateAvailableBalance();
    }

    protected function calculateAvailableBalance()
    {
        $sales = Sale::with(['collections'])
            ->where('agency_id', Auth::user()->agency_id)
            ->where('customer_id', $this->customer->id)
            ->get();

        $grouped = $sales->groupBy(function ($item) {
            return $item->sale_group_id ?? $item->id;
        });

        $customerSales = $grouped->map(function ($sales) {
            $first = $sales->first();
            return (object) [
                'id' => $first->id,
                'usd_sell' => $sales->sum('usd_sell'),
                'amount_paid' => $sales->sum('amount_paid'),
                'collections_total' => $sales->flatMap->collections->sum('amount'),
            ];
        });

        $this->availableBalanceToPayOthers = 0;

        foreach ($customerSales as $s) {
            $total = $s->usd_sell;
            $paid = $s->amount_paid;
            $collected = $s->collections_total;
            $remaining = $total - $paid - $collected;

            $refundAmount = Sale::where('sale_group_id', $s->id)
                ->whereIn('status', ['Refund-Full', 'Refund-Partial'])
                ->sum('usd_sell');

            $remaining -= $refundAmount;

            if ($remaining < 0) {
                $this->availableBalanceToPayOthers += abs($remaining);
            }
        }

        $usedForOthers = \App\Models\Collection::whereHas('sale', function ($q) {
            $q->where('customer_id', $this->customer->id);
        })
            ->where('note', 'like', '%تسديد من رصيد الشركة للعميل%')
            ->sum('amount');

        $this->availableBalanceToPayOthers = max(0, $this->availableBalanceToPayOthers - $usedForOthers);
    }

    public function calculateFinancials()
    {
        $currency = Auth::user()->agency->currency ?? 'USD';
        Log::channel('customer_accounts')->info('[calculateFinancials] بدء حساب التحصيلات', ['customer_id' => $this->customerId]);

        // 1. حساب إجمالي المبيعات النشطة
        $activeSales = $this->sales->whereNotIn('status', ['Void'])->sum('usd_sell');
        Log::debug('إجمالي المبيعات النشطة', ['amount' => $activeSales, 'currency' => $currency]);

        // 2. حساب المدفوعات المباشرة
        $directPayments = $this->collections->sum('amount');
        Log::debug('المدفوعات المباشرة (التحصيلات)', [
            'amount' => $directPayments,
            'count' => $this->collections->count(),
            'details' => $this->collections->pluck('amount', 'id')
        ]);

        // 3. حساب المدفوعات الكاملة (kash)
        $fullPayments = $this->sales
            ->where('payment_method', 'kash')
            ->whereNotIn('status', ['Refund-Full', 'Refund-Partial'])
            ->sum('usd_sell');

        Log::debug('المدفوعات الكاملة (kash)', [
            'amount' => $fullPayments,
            'sales' => $this->sales->where('payment_method', 'kash')->pluck('usd_sell', 'id')
        ]);

        // 4. حساب المدفوعات الجزئية والاستردادات
        $partialPayments = 0;
        $validRefunds = 0;
        $groupedSales = $this->sales->groupBy(function ($sale) {
            return $sale->sale_group_id ?? $sale->id;
        });

        Log::debug('عدد مجموعات البيع', ['count' => $groupedSales->count()]);

        foreach ($groupedSales as $groupId => $group) {
            $totalPaid = 0;
            $totalRefunded = 0;

            foreach ($group as $sale) {
                if ($sale->payment_method == 'part' && !in_array($sale->status, ['Refund-Full', 'Refund-Partial'])) {
                    $totalPaid += $sale->amount_paid ?? 0;
                }

                if (in_array($sale->status, ['Refund-Full', 'Refund-Partial'])) {
                    $totalRefunded += abs($sale->usd_sell); // نستخدم القيمة المطلقة
                }
            }

            Log::debug("معالجة مجموعة البيع {$groupId}", [
                'total_paid' => $totalPaid,
                'total_refunded' => $totalRefunded,
                'net_payment' => max($totalPaid - $totalRefunded, 0)
            ]);

            if ($totalRefunded > 0) {
                if ($totalRefunded >= $totalPaid) {
                    // استرداد كامل - لا نضيف أي شيء للتحصيلات
                    $validRefunds += $totalPaid;
                    $partialPayments += 0;
                    Log::debug("استرداد كامل للمجموعة {$groupId} - تم إلغاء كامل المبلغ المدفوع");
                } else {
                    // استرداد جزئي - نضيف الفرق فقط
                    $partialPayments += ($totalPaid - $totalRefunded);
                    $validRefunds += $totalRefunded;
                    Log::debug("استرداد جزئي للمجموعة {$groupId} - تم خصم الجزء المسترد");
                }
            } else {
                // لا يوجد استرداد - نضيف المبلغ كاملاً
                $partialPayments += $totalPaid;
                Log::debug("لا يوجد استرداد للمجموعة {$groupId} - تم إضافة كامل المبلغ");
            }
        }
        // 5. حساب الإجماليات النهائية
        $netPayments = $directPayments + $fullPayments + $partialPayments;
        $netBalance = max($activeSales - $netPayments, 0);
        $totalRefundedAmount = $this->sales
            ->whereIn('status', ['Refund-Full', 'Refund-Partial'])
            ->sum('usd_sell');

        Log::info('النتائج النهائية', [
            'active_sales' => $activeSales,
            'direct_payments' => $directPayments,
            'full_payments' => $fullPayments,
            'partial_payments' => $partialPayments,
            'total_refunded' => $totalRefundedAmount,
            'valid_refunds' => $validRefunds,
            'net_payments' => $netPayments,
            'net_balance' => $netBalance,
            'calculation' => "{$directPayments} + {$fullPayments} + {$partialPayments} = {$netPayments}"
        ]);

        return [
            'active_sales' => $activeSales,
            'direct_payments' => $directPayments,
            'full_payments' => $fullPayments,
            'partial_payments' => $partialPayments,
            'refunded_amount' => $totalRefundedAmount,
            'valid_refunds' => $validRefunds,
            'net_payments' => $netPayments,
            'net_balance' => $netBalance,
            'available_balance' => $this->availableBalanceToPayOthers,
            'currency' => $currency
        ];
    }
    public function prepareTransactions()
    {
        $transactions = collect();

        // إضافة عمليات البيع
        foreach ($this->sales as $sale) {
            $transactions->push([
                'created_at' => $sale->created_at,
                'date' => $sale->sale_date,
                'type' => 'sale',
                'data' => $sale,
                'amount' => $sale->usd_sell,
                'is_refund' => in_array($sale->status, ['Refund-Full', 'Refund-Partial']),
                'is_partial' => $sale->payment_method == 'part',
                'partial_amount' => $sale->amount_paid ?? 0,
            ]);
        }

        // إضافة عمليات التحصيل
        foreach ($this->collections as $collection) {
            $transactions->push([
                'created_at' => $collection->created_at,
                'date' => $collection->payment_date,
                'type' => 'collection',
                'data' => $collection,
                'amount' => $collection->amount,
                'is_refund' => false,
                'is_partial' => false,
                'partial_amount' => 0,
            ]);
        }

        // ترتيب العمليات حسب وقت حدوثها
        return $transactions->sortBy('created_at');
    }

    public function render()
    {
        $accountNumber = \App\Models\Customer::where('agency_id', $this->customer->agency_id)
            ->orderBy('created_at')
            ->pluck('id')
            ->search($this->customer->id) + 1;

        $financials = $this->calculateFinancials();
        $sortedTransactions = $this->prepareTransactions();

        return view('livewire.agency.reportsView.customer-account-details', [
            'accountNumber' => $accountNumber,
            'activeSales' => $financials['active_sales'],
            'directPayments' => $financials['direct_payments'],
            'fullPayments' => $financials['full_payments'],
            'partialPayments' => $financials['partial_payments'],
            'refundedAmount' => $financials['refunded_amount'],
            'netPayments' => $financials['net_payments'],
            'netBalance' => $financials['net_balance'],
            'availableBalanceToPayOthers' => $financials['available_balance'],
            'currency' => $financials['currency'],
            'sortedTransactions' => $sortedTransactions,
        ]);
    }
}
