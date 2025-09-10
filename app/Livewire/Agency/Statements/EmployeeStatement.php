<?php

namespace App\Livewire\Agency\Statements;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class EmployeeStatement extends Component
{
    public User $user;

    public string $fromDate = '';
    public string $toDate   = '';
    public array  $selectedRows = [];

    /** @var array<int,array{no:int,date:string,desc:string,status:string,debit:float,credit:float,balance:float}> */
    public array $statement = [];

    public float $sumDebit = 0.0;   // عليه
    public float $sumCredit = 0.0;  // له
    public float $net = 0.0;        // الصافي = له - عليه

    public function mount(User $user)
    {
        abort_unless($user->agency_id === Auth::user()->agency_id, 403);
        $this->user = $user;
        $this->rebuild();
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['fromDate', 'toDate'], true)) {
            $this->rebuild();
        }
    }

    private function fmtDate($dt): string
    {
        if (!$dt) return '';
        try { return \Carbon\Carbon::parse($dt)->format('Y-m-d H:i:s'); }
        catch (\Throwable $e) { return (string)$dt; }
    }

    private function rebuild(): void
    {
                // EmployeeStatement::rebuild()
            $wallet = \App\Models\EmployeeWallet::firstOrCreate(
                ['user_id' => $this->user->id],
                ['balance' => 0, 'status' => 'active']
            );

            $tx = \App\Models\EmployeeWalletTransaction::where('wallet_id', $wallet->id)
                ->when($this->fromDate, fn($q) =>
                    $q->where('created_at', '>=', \Carbon\Carbon::parse($this->fromDate)->startOfDay())
                )
                ->when($this->toDate, fn($q) =>
                    $q->where('created_at', '<=', \Carbon\Carbon::parse($this->toDate)->endOfDay())
                )
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id','type','amount','running_balance','reference','note','created_at']);


        $rows = [];
        foreach ($tx as $t) {
            $type = strtolower((string)$t->type);

            // اتجاه القيد
            $isDebit = in_array($type, ['withdraw','sale_debt'], true);
            $debit  = $isDebit ? (float)$t->amount : 0.0;
            $credit = $isDebit ? 0.0 : (float)$t->amount;

            // وصف عربي مختصر
            $status = match ($type) {
                'deposit'              => 'إيداع',
                'withdraw'             => 'سحب',
                'adjust'               => 'تعديل رصيد',
                'commission_expected'  => 'عمولة متوقعة',
                'commission_adjust'    => 'تسوية عمولة',
                'commission_collected' => 'عمولة تحصيل',
                'debt_release'         => 'فك دين',
                'sale_debt'            => 'دين عمولة',
                'accrual_adjust'       => 'تسوية متوقّع',
                default                => ucfirst($type),
            };

            $raw  = (string)($t->note ?: $status);
            $raw  = preg_replace('/#\d+/', '', $raw);          // يحذف #5 ونظائرها
            $raw  = preg_replace('/\s{2,}/', ' ', $raw);       // تنظيف فراغات
            $desc = trim($raw);

            $rows[] = [
                'no'      => 0,
                'date'    => $this->fmtDate($t->created_at),
                'desc'    => $desc,
                'status'  => $status,
                'debit'   => $debit,   // عليه
                'credit'  => $credit,  // له
                'balance' => 0.0,      // سيُحتسب تراكميًا
                '_ord'    => 1,
            ];
        }

        // فرز احتياطي ثم الرصيد التراكمي
        usort($rows, function($a, $b) {
            $dateCompare = strcmp($a['date'], $b['date']);
            if ($dateCompare === 0) {
                return $a['_ord'] <=> $b['_ord'];
            }
            return $dateCompare;
        });
        $bal = 0.0; $i = 1;
        foreach ($rows as &$r) {
            $bal += (($r['debit'] ?? 0.0) - ($r['credit'] ?? 0.0));
            $r['balance'] = $bal;
            $r['no'] = $i++;
            unset($r['_ord']);
        }
        unset($r);

        $this->statement = $rows;
        $this->sumDebit  = round(array_sum(array_column($rows, 'debit')), 2);
        $this->sumCredit = round(array_sum(array_column($rows, 'credit')), 2);
        $this->net       = round($this->sumCredit - $this->sumDebit, 2); // له − عليه
        $this->selectedRows = [];
    }


    public function render()
    {
        return view('livewire.agency.statements.employee-statement', [
            'employee'  => $this->user,
            'statement' => $this->statement,
        ]);
    }

   public function exportPdf(?string $scope = 'all')
{
    $rows = $this->statement;

    if ($scope === 'selected') {
        $idx  = array_map('intval', $this->selectedRows);
        $rows = array_values(array_intersect_key($rows, array_flip($idx)));
        if (empty($rows)) {
           session()->flash('message', 'اختر صفاً واحداً على الأقل.');
           session()->flash('type', 'info');  
            return;
        }
    }

    $payload = base64_encode(json_encode([
        'user_id' => $this->user->id,
        'filters' => [
            'from' => $this->fromDate ?: null,
            'to'   => $this->toDate   ?: null,
        ],
        'rows'   => $rows,
        'totals' => [
            'count'  => count($rows),
            'debit'  => $this->sumDebit,
            'credit' => $this->sumCredit,
        ],
    ]));

    return redirect()->route('agency.statements.employee.pdf', [
        'user' => $this->user->id,
        'data' => $payload,
    ]);
}

public function exportPdfAuto()
{
    if (empty($this->selectedRows)) {
       session()->flash('message', 'اختر صفاً واحداً على الأقل.');
         session()->flash('type', 'info');  
        return;
    }
    return $this->exportPdf('selected');
}


    public function toggleSelectAll(): void
    {
        $total = count($this->statement);
        if ($total === 0) return;

        if (count($this->selectedRows) === $total) {
            $this->selectedRows = [];
        } else {
            $this->selectedRows = array_keys($this->statement);
        }
    }

    public function resetFilters(): void
    {
        $this->fromDate = '';
        $this->toDate   = '';
        $this->rebuild();
    }
}
