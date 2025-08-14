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


    private function statusArabicLabel(string $st): string
{
    $s = mb_strtolower(trim($st));
    if (str_contains($s, 'refund') && str_contains($s, 'partial')) return 'استرداد جزئي';
    if (str_contains($s, 'refund') && str_contains($s, 'full'))    return 'استرداد كلي';
    if ($s === 'void' || str_contains($s, 'cancel'))               return 'إلغاء';
    if ($s === 'issued' || str_contains($s, 'reissued'))           return 'تم الإصدار';
    if ($s === 'pending' || str_contains($s, 'submit'))            return 'قيد التقديم';
    return $st ?: '-';
}
private function fmtDate($dt): string
{
    if (!$dt) return '';
    try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i:s'); }
    catch (\Throwable $e) { return (string)$dt; }
}

/**
 * يبني نفس صفوف كشف الحساب: شراء/قيد التقديم، استرداد/إلغاء، مدفوعات مباشرة، والتحصيلات.
 * ويعيد الإجماليات (المبيعات = مدين، المدفوعات/الاستردادات = دائن).
 */
private function buildRowsLikeStatement(): array
{
    $sales = \App\Models\Sale::with(['collections','service','customer'])
        ->where('agency_id', Auth::user()->agency_id)
        ->where('customer_id', $this->customer->id)
        ->orderBy('created_at')
        ->get();

    $refund = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
    $void   = ['void','cancel','canceled','cancelled'];

    $rows = [];
    foreach ($sales->groupBy(fn($s) => $s->sale_group_id ?? $s->id) as $group) {
        $grpTs = $this->fmtDate($group->min('created_at'));
        foreach ($group as $sale) {
            $st          = mb_strtolower(trim($sale->status ?? ''));
            $serviceName = (string)($sale->service->label ?? '-');
            $benefName   = (string)($sale->beneficiary_name ?? $sale->customer->name ?? '-');

            // شراء/قيد التقديم
            if (!in_array($st, $refund, true) && !in_array($st, $void, true)) {
                $label = ($st === 'pending' || str_contains($st, 'submit'))
                    ? "قيد التقديم {$serviceName} لـ{$benefName}"
                    : "شراء {$serviceName} لـ{$benefName}";
                $rows[] = [
                    'date'=>$this->fmtDate($sale->created_at),'desc'=>$label,
                    'status'=>$this->statusArabicLabel($st),
                    'debit'=>(float)$sale->usd_sell,'credit'=>0.0,
                    '_grp'=>$grpTs,'_evt'=>$this->fmtDate($sale->created_at),'_ord'=>1,
                ];
            }

            // استرداد/إلغاء
            if (in_array($st, $refund, true) || in_array($st, $void, true)) {
                $label = $this->statusArabicLabel($st);
                $rows[] = [
                    'date'=>$this->fmtDate($sale->created_at),
                    'desc'=> "{$label} لـ{$serviceName} لـ{$benefName}",
                    'status'=>$label,'debit'=>0.0,'credit'=>abs((float)$sale->usd_sell),
                    '_grp'=>$grpTs,'_evt'=>$this->fmtDate($sale->created_at),'_ord'=>2,
                ];
            }

            // مدفوعات مباشرة من العملية (amount_paid)
            if ((float)$sale->amount_paid > 0) {
                $statusLabel = ($sale->payment_method === 'kash' && (float)$sale->amount_paid >= (float)$sale->usd_sell)
                    ? 'سداد كلي' : 'سداد جزئي';
                $rows[] = [
                    'date'=>$this->fmtDate($sale->created_at),
                    'desc'=> "{$statusLabel} {$serviceName} لـ{$benefName}",
                    'status'=>$statusLabel,'debit'=>0.0,'credit'=>(float)$sale->amount_paid,
                    '_grp'=>$grpTs,'_evt'=>$this->fmtDate($sale->created_at),'_ord'=>3,
                ];
            }

            // التحصيلات
            foreach ($sale->collections as $col) {
                $evt = $this->fmtDate($col->created_at ?? $col->payment_date);
                $rows[] = [
                    'date'=>$evt,'desc'=>"سداد من التحصيل {$serviceName} لـ{$benefName}",
                    'status'=>'سداد من التحصيل','debit'=>0.0,'credit'=>(float)$col->amount,
                    '_grp'=>$grpTs,'_evt'=>$evt,'_ord'=>4,
                ];
            }
        }
    }

    // فرز موحد
    usort($rows, fn($a,$b) => [$a['_grp'],$a['_evt'],$a['_ord']] <=> [$b['_grp'],$b['_evt'],$b['_ord']]);
    foreach ($rows as &$r) unset($r['_grp'],$r['_evt'],$r['_ord']);
    unset($r);

    // إجماليات مثل كشف الحساب
    $totalDebit  = array_sum(array_column($rows,'debit'));   // إجمالي المبيعات
    $totalCredit = array_sum(array_column($rows,'credit'));  // إجمالي التحصيل + المدفوعات + الاستردادات
    $netBalance  = max($totalDebit - $totalCredit, 0);       // المتبقي على العميل
    return [$rows, $totalDebit, $totalCredit, $netBalance];
}

public function calculateFinancials(): array
{
    [$rows, $totalDebit, $totalCredit, $netBalance] = $this->buildRowsLikeStatement();
    // نحتفظ بقيمة الرصيد المتاح السابقة كما هي
    return [
        'active_sales'   => $totalDebit,
        'direct_payments'=> 0,                // لم نعد نفصلها هنا
        'full_payments'  => 0,
        'partial_payments'=>0,
        'refunded_amount'=>0,
        'net_payments'   => $totalCredit,     // كل الدائن
        'net_balance'    => $netBalance,
        'available_balance' => $this->availableBalanceToPayOthers,
        'currency'       => Auth::user()->agency->currency ?? 'USD',
        'rows'           => $rows,
    ];
}

/** لم نعد نحتاج prepareTransactions */
public function prepareTransactions()
{
    [$rows] = $this->buildRowsLikeStatement();
    // نحولها لمجموعة مرتبة تُعرض في الجدول
    return collect($rows);
}

public function render()
{
    $accountNumber = Customer::where('agency_id',$this->customer->agency_id)
        ->orderBy('created_at')->pluck('id')->search($this->customer->id) + 1;

    $financials        = $this->calculateFinancials();
    $sortedTransactions= collect($financials['rows']); // نفس صفوف كشف الحساب

    return view('livewire.agency.reportsView.customer-account-details', [
        'accountNumber' => $accountNumber,
        'activeSales'   => $financials['active_sales'],
        'directPayments'=> 0,
        'fullPayments'  => 0,
        'partialPayments'=>0,
        'refundedAmount'=> 0,
        'netPayments'   => $financials['net_payments'],
        'netBalance'    => $financials['net_balance'],
        'availableBalanceToPayOthers' => $financials['available_balance'],
        'currency'      => $financials['currency'],
        'sortedTransactions' => $sortedTransactions,
    ]);
}

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





    
}
