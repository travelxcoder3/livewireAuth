<?php

namespace App\Livewire\Sales;

use App\Models\Provider;
use App\Models\Sale;
use App\Models\ServiceType;
use App\Models\Intermediary;
use App\Models\Customer;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Beneficiary;
use App\Tables\SalesTable;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\CommissionProfile;
use App\Models\EmployeeMonthlyTarget;
use Carbon\Carbon;
use App\Models\ApprovalSequenceUser;
use App\Models\ApprovalRequest;
use App\Models\ApprovalSequence;
use Illuminate\Support\Facades\Schema;
use App\Notifications\SaleEditApprovalPending;
use App\Services\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Index extends Component
{
    use WithPagination;

    public $beneficiary_name, $sale_date, $provider_id,
        $customer_via, $usd_buy, $usd_sell, $commission, $route, $pnr, $reference,
        $status, $amount_paid, $depositor_name, $account_id, $customer_id, $sale_profit = 0,
        $payment_method, $payment_type, $receipt_number, $phone_number, $service_type_id, $service_date, $expected_payment_date;

    public $showCommission = false;
    public $userCommission     = 0;
    public $userCommissionDue  = 0;
    public $editingSale = null;
    public $currency;
    public $totalAmount = 0;
    public $totalReceived = 0;
    public $totalPending = 0;
    public $totalProfit = 0;
    public $amount_due = 0;
    public $services = [];
    public $showExpectedDate = false;
    public $showCustomerField = true;
    public $showPaymentDetails = true;
    public $showDepositorField = true;
    public ?string $sale_group_id = null;
    public bool $isDuplicated = false;
    public bool $showRefundModal = false;
    public bool $showAmountPaidField = true;
    public bool $disablePaymentMethod = false;
    public bool $commissionReadOnly = false;
    public array $statusOptions = [];
    public $original_user_id = null;
    public int $sale_edit_hours = 72;
    public bool $isSaving = false;

    // 👇 إضافة scope
    public $filters = [
        'start_date' => '',
        'end_date' => '',
        'service_type_id' => '',
        'status' => '',
        'customer_id' => '',
        'provider_id' => '',
        'service_date' => '',
        'customer_via' => '',
        'route' => '',
        'payment_method' => '',
        'payment_type' => '',
        'reference' => '',
        'scope' => 'mine', // افتراضيًا: عملي فقط
    ];

    // 👇 إضافة scope
    public $filterInputs = [
        'start_date' => '',
        'end_date' => '',
        'service_type_id' => '',
        'status' => '',
        'customer_id' => '',
        'provider_id' => '',
        'service_date' => '',
        'customer_via' => '',
        'route' => '',
        'payment_method' => '',
        'payment_type' => '',
        'reference' => '',
        'scope' => 'mine',
    ];

    public $filterServices = [];
    public $filterCustomers = [];

    public string $customerSearch = '';
    public array  $customerOptions = [];

    public string $providerSearch = '';
    public array  $providerOptions = [];
    public string $customerLabel = '';
    public string $providerLabel = '';
    public int $formKey = 0;
    public array $commissionExceptions = [
        // 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
    ];

    public $successMessage;

    private function editWindowMinutes(): int
    {
        return (int)(Auth::user()->agency?->editSaleWindowMinutes() ?? 180);
    }

    private function findValidEditApproval(int $saleId): ?\App\Models\ApprovalRequest
{
    $minutes = $this->editWindowMinutes();

    return \App\Models\ApprovalRequest::where('model_type', \App\Models\Sale::class)
        ->where('model_id', $saleId)
        ->where('status', 'approved')
        ->when(\Illuminate\Support\Facades\Schema::hasColumn('approval_requests','agency_id'), function ($q) {
            $q->where('agency_id', auth()->user()->agency_id);
        })
        ->where('created_at', '>=', now()->subMinutes($minutes)) // موافقة حديثة ضمن النافذة
        ->latest('id')
        ->first();
}

    
    private function remainingEditableMinutesFor(Sale $sale): int
{
    $window = max(0, $this->editWindowMinutes()); // من سياسة الوكالة (مثلاً 180)
    if ($window === 0) {
        return 0;
    }

    // (1) نافذة الإنشاء الأولى
    $createdDeadline   = $sale->created_at->copy()->addMinutes($window);
    $fromCreationLeft  = max(0, now()->diffInMinutes($createdDeadline, false));

    // (2) نافذة آخر موافقة على طلب تعديل (إن وُجد)
    $approval = $this->findValidEditApproval($sale->id); // تستخدم الدالة الموجودة لديك
    $fromApprovalLeft = 0;
    if ($approval) {
        $approvalDeadline  = $approval->created_at->copy()->addMinutes($window);
        $fromApprovalLeft  = max(0, now()->diffInMinutes($approvalDeadline, false));
    }

    // المسموح = أكبر المتبقّيَين
    return max($fromCreationLeft, $fromApprovalLeft);
}

    private function createEditApprovalRequest(Sale $sale): void
    {
        $sequence = ApprovalSequence::where('agency_id', Auth::user()->agency_id)
            ->where('action_type', 'sale_edit')
            ->first();

        if (!$sequence) return;

        $alreadyPending = ApprovalRequest::where('model_type', Sale::class)
            ->where('model_id', $sale->id)
            ->where('status', 'pending')
            ->when(Schema::hasColumn('approval_requests', 'agency_id'), function ($q) {
                $q->where('agency_id', Auth::user()->agency_id);
            })
            ->exists();

        if ($alreadyPending) return;

        $data = [
            'approval_sequence_id' => $sequence->id,
            'model_type'           => Sale::class,
            'model_id'             => $sale->id,
            'status'               => 'pending',
            'requested_by'         => Auth::id(),
        ];
        if (Schema::hasColumn('approval_requests', 'agency_id')) {
            $data['agency_id'] = Auth::user()->agency_id;
        }
        if (Schema::hasColumn('approval_requests', 'notes')) {
            $data['notes'] = 'طلب تعديل عملية بيع #'.$sale->id;
        }

        ApprovalRequest::create($data);

        // إشعار الموافقين (AppNotification)
        $approverIds = ApprovalSequenceUser::where('approval_sequence_id', $sequence->id)
            ->pluck('user_id')->all();

        if (!empty($approverIds)) {
            Notify::toUsers(
                $approverIds,
                'طلب تعديل عملية بيع',
                "هناك طلب تعديل للعملية رقم #{$sale->id}",
                route('agency.approvals.index'),
                'sale_edit',
                Auth::user()->agency_id
            );
        }
    }

    // اطلب موافقة تعديل لعملية معيّنة
public function request_edit(int $saleId): void
{
    $sale = \App\Models\Sale::findOrFail($saleId);

    // لو مازالت نافذة التعديل سارية: لا حاجة لطلب موافقة
    if ($this->remainingEditableMinutesFor($sale) > 0) {
        $this->addError('general', 'لا حاجة لطلب تعديل: ما زالت مدة التعديل مفتوحة.');
        return;
    }

    // إنشاء طلب الموافقة + إشعار الموافقين
    $this->createEditApprovalRequest($sale);
    // تغذية راجعة للمستخدم وتحديث الجرس
    session()->flash('message', 'تم إرسال طلب تعديل لهذه العملية وبانتظار الموافقة.');
    $this->dispatch('refreshNotifications');
}

// Alias احتياطي إن كان الواجهة تستدعي camelCase
// ➋ طلب تعديل يدوي عند انتهاء المهلة
public function requestEdit(int $id): void
{
    $sale = \App\Models\Sale::findOrFail($id);

    // لو مازالت المهلة سارية، وجّه المستخدم للتعديل مباشرة
    if ($this->remainingEditableMinutesFor($sale) > 0) {
        $this->addError('general', 'التعديل متاح الآن، اضغط "تعديل" مباشرة.');
        return;
    }

    $this->createEditApprovalRequest($sale);

    $this->successMessage = 'تم إرسال طلب التعديل وبانتظار الموافقة.';
    $this->dispatch('approval-state-updated'); // يحدّث الجدول فورًا
}

    public function refreshCustomerOptions()
    {
        $term = trim($this->customerSearch);

        $this->customerOptions = \App\Models\Customer::query()
            ->where('agency_id', auth()->user()->agency_id)
            ->when($term !== '', function ($q) use ($term) {
                $len  = mb_strlen($term);
                $like = $len >= 3 ? "%{$term}%" : "{$term}%";
                $q->where('name', 'like', $like);
            })
            ->select('id','name')
            ->orderBy('name')
            ->limit(20)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function refreshProviderOptions()
    {
        $term = trim($this->providerSearch);

        $this->providerOptions = \App\Models\Provider::query()
            ->where('agency_id', auth()->user()->agency_id)
            ->where('status', 'approved')
            ->when($term !== '', function ($q) use ($term) {
                $len  = mb_strlen($term);
                $like = $len >= 3 ? "%{$term}%" : "{$term}%";
                $q->where('name', 'like', $like);
            })
            ->select('id','name')
            ->orderBy('name')
            ->limit(20)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedCustomerSearch()
    {
        $len = mb_strlen($this->customerSearch);
        if ($len === 0 || $len >= 2) {
            $this->refreshCustomerOptions();
        }
        $this->skipRender();
    }

    public function updatedProviderSearch()
    {
        $len = mb_strlen($this->providerSearch);
        if ($len === 0 || $len >= 2) {
            $this->refreshProviderOptions();
        }
        $this->skipRender();
    }

    public function fetchServices()
    {
        $this->services = \App\Models\DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة الخدمات');
        })
        ->where(function($query) {
            $query->where('created_by_agency', auth()->user()->agency_id)
                  ->orWhereNull('created_by_agency');
        })
        ->get();
    }

    public function duplicate($id)
    {
        $sale = Sale::findOrFail($id);
        $this->isDuplicated = true;
        $this->original_user_id = $sale->user_id;
        $this->updateStatusOptions();

        $this->showAmountPaidField = !in_array($sale->status, ['Refund-Full', 'Refund-Partial', 'Void']);

        $this->beneficiary_name = $sale->beneficiary_name;
        $this->sale_date = $sale->sale_date;
        $this->service_type_id = $sale->service_type_id;
        $this->provider_id = $sale->provider_id;
        $this->customer_via = $sale->customer_via;
        $this->usd_buy = $sale->usd_buy;
        $this->usd_sell = $sale->usd_sell;
        $this->commission = $sale->commission;
        if ($this->showCommission) {
            if (!is_null($sale->commission)) {
                $this->commission = $sale->commission;
            }
            if ($sale->status === 'Refund-Full') {
                $this->commission = 0;
                $this->commissionReadOnly = true;
            } elseif (!is_null($sale->commission)) {
                $this->commissionReadOnly = true;
            } else {
                $this->commissionReadOnly = false;
            }
        }

        $this->route = $sale->route;
        $this->pnr = $sale->pnr;
        $this->reference = $sale->reference;
        $this->status = $sale->status;
$this->amount_paid = null; // لا ترث المدفوع عند التكرار
        $this->depositor_name = $sale->depositor_name;
        $this->customer_id = $sale->customer_id;
        $this->sale_profit = $sale->sale_profit;
        $this->payment_method = $sale->payment_method;
        $this->payment_type = $sale->payment_type;
        $this->receipt_number = $sale->receipt_number;
        $this->phone_number = $sale->phone_number;
        $this->service_date = $sale->service_date;
        $this->expected_payment_date = $sale->expected_payment_date;
        $this->sale_group_id = $sale->sale_group_id;

        $this->showExpectedDate = in_array($sale->payment_method, ['part', 'all']);

        $customer = \App\Models\Customer::find($sale->customer_id);
        $this->showCommission = $customer && $customer->has_commission;

        $this->calculateProfit();
        $this->calculateDue();

        $this->showExpectedDate = in_array($sale->payment_method, ['part', 'all']);
        $this->showPaymentDetails = $sale->payment_method !== 'all';
        $this->showDepositorField = $sale->payment_method !== 'all';

        if ($sale->payment_method === 'all') {
            $this->payment_type = null;
            $this->receipt_number = null;
            $this->depositor_name = null;
        }

        $this->showCustomerField = true;

        $customer = \App\Models\Customer::find($sale->customer_id);
        $this->showCommission = $customer && $customer->has_commission;

        if (!$this->showCommission) {
            $this->commission = null;
        }

        if ($sale->payment_method === 'all') {
            $this->amount_paid = null;
        }

        if ($sale->customer_id) {
            $c = \App\Models\Customer::find($sale->customer_id);
            $this->customerLabel = $c->name ?? '';
        }
        if ($sale->provider_id) {
            $p = \App\Models\Provider::find($sale->provider_id);
            $this->providerLabel = $p->name ?? '';
        }

        $this->formKey++;

        $this->status = null;
        $this->dispatch('$refresh');
    }

    public function resetForm()
    {
        $this->reset([
            'beneficiary_name',
            'sale_date',
            'service_type_id',
            'provider_id',
            'customer_via',
            'usd_buy',
            'usd_sell',
            'commission',
            'route',
            'pnr',
            'reference',
            'status',
            'amount_paid',
            'depositor_name',
            'customer_id',
            'sale_profit',
            'payment_method',
            'payment_type',
            'receipt_number',
            'phone_number',
            'service_date',
            'expected_payment_date',
        ]);

        $this->showPaymentDetails = true;
        $this->showDepositorField = true;
        $this->showAmountPaidField = true;
        $this->showExpectedDate   = false;
        $this->showRefundModal    = false;
        $this->disablePaymentMethod = false;

        $this->sale_profit = 0;
        $this->amount_due  = 0;
        $this->showCommission = false;
        $this->commission = null;
        $this->commissionReadOnly = false;

        $this->sale_group_id  = Str::uuid();

        $this->isDuplicated = false;
        $this->updateStatusOptions();
        $this->editingSale = null;

        $this->customerLabel = '';
        $this->providerLabel = '';

        $this->customerSearch = '';
        $this->providerSearch = '';
        $this->refreshCustomerOptions();
        $this->refreshProviderOptions();

        $this->formKey++;
        $this->dispatch('lw-dropdowns-cleared');
    }

    public function updatedFilterInputsCustomerId($value)
    {
        $c = Customer::find($value);
        $this->customerLabel = $c->name ?? '';
    }

    public function updatedFilterInputsProviderId($value)
    {
        $p = Provider::find($value);
        $this->providerLabel = $p->name ?? '';
    }

    public function resetFields()
    {
        $this->resetForm();
        $this->resetValidation();
        $this->successMessage = null;
    }

    public function getFilteredProviders()
    {
        return Provider::query()
            ->where('agency_id', Auth::user()->agency_id)
            ->where('status', 'approved')
            ->get();
    }

    public function updateStatusOptions()
    {
        $this->statusOptions = $this->isDuplicated
            ? [
                'Re-Issued' => 'أعيد الإصدار - Re-Issued',
                'Re-Route' => 'تغيير المسار - Re-Route',
                'Refund-Full' => 'استرداد كلي - Refund Full',
                'Refund-Partial' => 'استرداد جزئي - Refund Partial',
                'Void' => 'ملغي نهائي - Void',
                'Rejected' => 'مرفوض - Rejected',
                'Approved' => 'مقبول - Approved',
            ]
            : [
                'Issued' => 'تم الإصدار - Issued',
                'Applied' => 'قيد التقديم - Applied',
            ];
    }

    public function render()
    {
        $user = Auth::user();
        $agency = $user->agency;

        if ($agency->parent_id) {
            if ($user->hasRole('agency-admin')) {
                $userIds = $agency->users()->pluck('id')->toArray();
                $salesQuery = Sale::where('agency_id', $agency->id)
                                  ->whereIn('user_id', $userIds);
            } else {
                $salesQuery = Sale::where('agency_id', $agency->id);

                if (!empty($this->filters['reference']) && $this->onlyReferenceFilter()) {
                    // لا تقييد
                } else {
                    $salesQuery->where('user_id', $user->id);
                }
            }
        } else {
            if ($user->hasRole('agency-admin')) {
                $branchIds = $agency->branches()->pluck('id')->toArray();
                $allAgencyIds = array_merge([$agency->id], $branchIds);
                $salesQuery = Sale::whereIn('agency_id', $allAgencyIds);
            } else {
                $salesQuery = Sale::where('agency_id', $agency->id);

                if (!empty($this->filters['reference']) && $this->onlyReferenceFilter()) {
                    // لا تقييد
                } else {
                    $salesQuery->where('user_id', $user->id);
                }
            }
        }

        // 👇 تقييد إضافي لمشرف الوكالة ليشاهد "عملي فقط" عندما scope=mine
        if ($user->hasRole('agency-admin')) {
            $scope = $this->filters['scope'] ?? 'mine';
            $onlyRef = !empty($this->filters['reference']) && $this->onlyReferenceFilter();
            if ($scope === 'mine' && !$onlyRef) {
                $salesQuery->where('user_id', $user->id);
            }
        }

        // الفلاتر
        $salesQuery->when($this->filters['start_date'], function($query) {
            $query->where('sale_date', '>=', $this->filters['start_date']);
        })
        ->when($this->filters['end_date'], function($query) {
            $query->where('sale_date', '<=', $this->filters['end_date']);
        })
        ->when($this->filters['service_type_id'], function($query) {
            $query->where('service_type_id', $this->filters['service_type_id']);
        })
        ->when($this->filters['status'], function($query) {
            $query->where('status', $this->filters['status']);
        })
        ->when($this->filters['customer_id'], function($query) {
            $query->where('customer_id', $this->filters['customer_id']);
        })
        ->when($this->filters['provider_id'], function($query) {
            $query->where('provider_id', $this->filters['provider_id']);
        })
        ->when($this->filters['service_date'], function($query) {
            $query->where('service_date', $this->filters['service_date']);
        })
        ->when($this->filters['customer_via'], function($query) {
            $query->where('customer_via', $this->filters['customer_via']);
        })
        ->when($this->filters['route'], function($query) {
            $query->where('route', 'like', '%'.$this->filters['route'].'%');
        })
        ->when($this->filters['payment_method'], function($query) {
            $query->where('payment_method', $this->filters['payment_method']);
        })
        ->when($this->filters['reference'], function($q){
            $t = trim($this->filters['reference']);
            $like = mb_strlen($t) >= 3 ? "%$t%" : "$t%";
            $q->where('reference', 'like', $like);
        })
        ->when($this->filters['payment_type'], function($query) {
            $query->where('payment_type', $this->filters['payment_type']);
        });

        $sales = (clone $salesQuery)
            ->with([
                'user:id,name','provider:id,name','service:id,label',
                'customer:id,name','updatedBy:id,name','duplicatedBy:id,name',
            ])
            ->withSum('collections','amount')
            ->orderByDesc('sale_date')->orderByDesc('id')
            ->simplePaginate(10);

        $sales->each(function ($sale) {
            $sale->total_paid = ($sale->amount_paid ?? 0) + ($sale->collections_sum_amount ?? 0);
            $sale->remaining_payment = max(0, ($sale->usd_sell ?? 0) - $sale->total_paid);
        });

        $intermediaries = Intermediary::select('id','name')->orderBy('name')->get();
        $accounts = Account::select('id','name')->orderBy('name')->get();

        // إجماليات الموظف الحالي
     // كون موحّد للمجموعات لموظفك وعلى نفس فلاتر الجدول
$groupUniverse = (clone $salesQuery)
    ->where('user_id', Auth::id())
    ->where('status','!=','Void')
    ->withSum('collections','amount')
    ->get(['id','sale_group_id','usd_sell','amount_paid','sale_profit']);

// اجمع حسب sale_group_id فقط، وإن كان null ادمجه على مفتاح اصطناعي لكل مجموعة
$byGroup = $groupUniverse->groupBy(function($s){
    return $s->sale_group_id ?: ('solo:'.$s->id); // يمنع دمج سطور غير مرتبطة خطأً
});

$totalAmount = $totalReceived = $totalPending = $totalProfit = 0.0;

foreach ($byGroup as $g) {
    $sell = (float) $g->sum('usd_sell');
    if ($sell <= 0) continue;                        // تجاهل السالب/الصفري
    $collected = (float) $g->sum('amount_paid') +    // مدفوع مباشر
                 (float) $g->sum('collections_sum_amount'); // تحصيلات

    $profit = (float) $g->sum('sale_profit');

    $totalAmount   += $sell;
    $totalReceived += min($collected, $sell);        // سقف عند البيع
    $totalProfit   += $profit;
}

$totalPending = max($totalAmount - $totalReceived, 0);

$this->totalAmount   = $totalAmount;
$this->totalReceived = $totalReceived;
$this->totalPending  = $totalPending;
$this->totalProfit   = $totalProfit;


        // تحديد شهر المرجع من الفلاتر إن كانت داخل نفس الشهر، وإلا شهر اليوم
        $ref = now();
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $s = Carbon::parse($this->filters['start_date']);
            $e = Carbon::parse($this->filters['end_date']);
            if ($s->isSameMonth($e)) { $ref = $s; }
        }
        $year  = (int) $ref->year;
        $month = (int) $ref->month;

        // هدف الشهر من جدول employee_monthly_targets وإلا من users.main_target
        $target = (float) (EmployeeMonthlyTarget::where('user_id', $user->id)
                    ->where('year', $year)->where('month', $month)->value('main_target')
                ?? $user->main_target ?? 0);

            // نسبة الشهر: Override إن وُجد، وإلا نسبة بروفايل الوكالة
            $override = \App\Models\EmployeeMonthlyTarget::where('user_id',$user->id)
                ->where('year',$year)->where('month',$month)->value('override_rate');

            $ratePct = ($override !== null)
                ? (float)$override
                : (float)(CommissionProfile::where('agency_id',$agency->id)
                    ->where('is_active', true)->value('employee_rate') ?? 0);
            $rate = $ratePct / 100.0;


        // أرباح الشهر حسب sale_date وتجميع profit المُحصّل فقط للـDue
        $monthRows = Sale::where('agency_id', $agency->id)
            ->where('user_id', $user->id)
            ->where('status','!=','Void')
            ->whereYear('sale_date', $year)
            ->whereMonth('sale_date', $month)
            ->withSum('collections','amount')
->get(['id','usd_sell','amount_paid','sale_profit','sale_group_id','status']);

        $groupedM = $monthRows->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

        $monthProfit = 0.0;
$monthCollectedProfit = 0.0;

foreach ($groupedM as $g) {
    $netSell      = (float) $g->sum('usd_sell');
    $netCollected = (float) $g->sum('amount_paid') + (float) $g->sum('collections_sum_amount');
    $gProfit      = (float) $g->sum('sale_profit');

    // هل بالمجموعة سطر استرداد؟ نكتشفه بالحالة أو بقيمة بيع سالبة
    $hasRefund = $g->contains(function ($row) {
        $st = mb_strtolower((string)($row->status ?? ''));
        return str_contains($st, 'refund') || (float)$row->usd_sell < 0;
    });

    // الربح المتوقع: إن وُجد Refund تجاهل السالب وخذ الإيجابي فقط
    if ($hasRefund) {
        $positiveOnly = (float) $g->filter(fn($row) => (float)$row->sale_profit > 0)
                                  ->sum('sale_profit');
        $monthProfit += max($positiveOnly, 0.0);
    } else {
        $monthProfit += $gProfit;
    }

    // الربح المستحق يبقى بالحساب الواقعي دون أي استثناء
    if ($netCollected + 0.01 >= $netSell) {
        $monthCollectedProfit += $gProfit;
    }
}



        $this->userCommission    = max(($monthProfit - $target) * $rate, 0);
        $this->userCommissionDue = max(($monthCollectedProfit - $target) * $rate, 0);


        return view('livewire.sales.index', [
            'sales'           => $sales,
            'services'        => $this->services,
            'intermediaries'  => $intermediaries,
            'accounts'        => $accounts,
            'providerOptions' => $this->providerOptions,
            'customerOptions' => $this->customerOptions,
            'filterServices'  => $this->filterServices,
            'filterCustomers' => $this->filterCustomers,
            'columns'         => SalesTable::columns(false, false),
        ])->layout('layouts.agency');
    }

    public function calculateProfit()
    {
        if (is_numeric($this->usd_buy) && is_numeric($this->usd_sell)) {
            $this->sale_profit = $this->usd_sell - $this->usd_buy;
        } else {
            $this->sale_profit = 0;
        }
    }

    public function updatedUsdSell()
    {
        $this->calculateDue();
        $this->calculateProfit();
    }

    public function calculateDue()
    {
        if (is_numeric($this->usd_sell)) {
            if ($this->payment_method === 'all' || $this->amount_paid === null || $this->amount_paid === '') {
                $this->amount_due = round($this->usd_sell, 2);
            } elseif (is_numeric($this->amount_paid)) {
                $this->amount_due = round($this->usd_sell - $this->amount_paid, 2);
            } else {
                $this->amount_due = round($this->usd_sell, 2);
            }
        } else {
            $this->amount_due = 0;
        }
    }

    public function confirmDuplicate(int $id): void
{
    $sale = \App\Models\Sale::findOrFail($id);
    $this->dispatch('confirm:open',
        title: 'تأكيد تكرار العملية',
        message: "سيتم إنشاء نسخة من العملية رقم {$sale->id} للمستفيد {$sale->beneficiary_name}. متابعة؟",
        icon: 'check',
        confirmText: 'تكرار',
        cancelText: 'إلغاء',
        onConfirm: 'duplicate',
        payload: $id
    );
}

public function confirmEdit(int $id): void
{
    $sale = \App\Models\Sale::findOrFail($id);
    $this->dispatch('confirm:open',
        title: 'تأكيد تعديل العملية',
        message: "ستعدل بيانات العملية رقم {$sale->id}. المتابعة؟",
        icon: 'info',
        confirmText: 'تعديل',
        cancelText: 'إلغاء',
        onConfirm: 'edit',
        payload: $id
    );
}

public function confirmRequestEdit(int $id): void
{
    $sale = \App\Models\Sale::findOrFail($id);
    $this->dispatch('confirm:open',
        title: 'إرسال طلب تعديل',
        message: "سيتم إرسال طلب تعديل للعملية رقم {$sale->id}. متابعة الإرسال؟",
        icon: 'warn',
        confirmText: 'إرسال الطلب',
        cancelText: 'إلغاء',
        onConfirm: 'requestEdit',
        payload: $id
    );
}


    public function updated($propertyName)
    {
        if (in_array($propertyName, ['usd_buy', 'usd_sell'])) {
            $this->calculateProfit();
            $this->calculateDue();
        }

        if ($propertyName === 'amount_paid') {
            $this->calculateDue();
        }
    }

    public function mount()
    {
        logger('FILTER INPUTS INITIAL:', $this->filterInputs);
        $this->updateStatusOptions();

        $this->currency = auth()->user()->agency->currency ?? 'USD';
        $this->sale_date = now()->format('Y-m-d');
        $this->fetchServices();
        $this->showExpectedDate = false;

        if (!$this->sale_group_id) {
            $this->sale_group_id = (string) Str::uuid();
        }

        $this->filterServices = \App\Models\DynamicListItem::whereHas('list', function($query) {
            $query->where('name', 'قائمة الخدمات');
        })->pluck('label', 'id')->toArray();

        $this->refreshCustomerOptions();
        $this->refreshProviderOptions();

        $this->filterCustomers = [];

        if ($this->customer_id) {
            $c = Customer::find($this->customer_id);
            $this->customerLabel = $c->name ?? '';
        }
        if ($this->provider_id) {
            $p = Provider::find($this->provider_id);
            $this->providerLabel = $p->name ?? '';
        }

        $this->sale_edit_hours = (int) (auth()->user()->agency->sale_edit_hours
    ?? config('agency.defaults.sale_edit_hours', 72));

    }

    protected function getListeners()
    {
        return [
            'payment-collected'       => 'refreshSales',
            'approval-state-updated'  => 'refreshSales', // عند الموافقة/الرفض/إنشاء الطلب
            'sales-tick'              => 'refreshSales', // نبض كل X ثواني
            
        ];
    }

    public function refreshSales()
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    protected function rules()
    {
        $today = now()->format('Y-m-d');

        $rules = [
            'beneficiary_name' => 'required|string|max:255',
            'sale_date' => ['required', 'date', 'before_or_equal:' . $today],
            'service_type_id' => 'required|exists:dynamic_list_items,id',
            'provider_id' => 'required|exists:providers,id',
            'customer_via' => 'required|in:whatsapp,facebook,instagram,call,office,other',
            'usd_buy' => [
                'required',
                'numeric',
                Rule::when(!in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void']), fn() => ['min:0']),
            ],
            'usd_sell' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if (in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void'])) {
                        if ($value >= 0) {
                            $fail('في حالة الاسترداد، يجب أن يكون سعر البيع سالبًا.');
                        }
                    } else {
                        if (!is_numeric($this->usd_buy) || $value < $this->usd_buy) {
                            $fail('البيع ≥ الشراء.');
                        }
                        if ($value < 0) {
                            $fail('سعر البيع لا يمكن أن يكون سالبًا.');
                        }
                    }
                },
            ],
            'commission' => 'nullable|numeric',
            'route' => 'required|string|max:255',
            'pnr' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:50',
            'amount_paid' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    // بعد: اسمح بـ null أو 0 في الاسترداد، وامنع القيم السالبة دائمًا
                        if (in_array($this->status, ['Refund-Full','Refund-Partial','Void'])) {
                            if (!is_null($value) && (float)$value !== 0.0) {
                                $fail('في الاسترداد يجب أن يكون amount_paid فارغًا أو 0.');
                            }
                        } elseif ($value < 0) {
                            $fail('المبلغ المدفوع لا يمكن أن يكون سالبًا.');
                        }

                },
            ],
            'depositor_name' => $this->payment_method !== 'all' ? 'required|string|max:255' : 'nullable',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_profit' => 'nullable|numeric',
            'receipt_number' => 'nullable|string|max:50',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:Issued,Re-Issued,Re-Route,Refund-Full,Refund-Partial,Void,Applied,Rejected,Approved',
            'payment_method' => 'required|in:kash,part,all',
            'payment_type' => $this->payment_method !== 'all' ? 'required|in:cash,transfer,account_deposit,fund,from_account,wallet,other' : 'nullable',
            'service_date' => 'required|date',
            'expected_payment_date' => 'nullable|date',
        ];

     if (in_array($this->status, ['Refund-Full','Refund-Partial','Void'])) {
    $rules['amount_paid'] = 'nullable|numeric';   // ← لا Required
    // لا نغيّر بقية الحقول
} else {
    switch ($this->payment_method) {
            case 'kash':
                $rules['customer_id'] = 'nullable|exists:customers,id';
                $rules['amount_paid'] = ['required', 'numeric', function ($attribute, $value, $fail) {
                    if (in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void'])) {
                        return;
                    }
                    if (floatval($value) !== floatval($this->usd_sell)) {
                        $fail('الدفع كاش، يشترط الدفع كامل.');
                    }
                }];
                break;

            case 'part':
                $rules['customer_id'] = 'required';
                $rules['amount_paid'] = [
                    'required',
                    'numeric',
                    function ($attribute, $value, $fail) {
                        $sell = floatval(trim($this->usd_sell));
                        $paid = floatval(trim($value));
                        if (in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void'])) {
                            return;
                        }
                        if (!is_numeric($sell) || $paid >= $sell) {
                            $fail('ادخل مبلغ صحيح.');
                        }
                    },
                ];
                break;

            case 'all':
                $rules['customer_id'] = 'required';
                $rules['amount_paid'] = 'prohibited';
                break;
           }
}

        return $rules;
    }

    protected $messages = [
        'usd_sell.gte' => 'البيع ≥ الشراء.',
        'amount_paid.max' => 'المبلغ كبير.',
        'customer_id.required' => 'حدد الحساب.',
        'amount_paid.lt' => ' ادخل مبلغ صحيح.',
        'amount_paid.required' => 'أدخل المبلغ.',
        'amount_paid.prohibited' => 'احذف المبلغ.',
        'sale_date.before_or_equal' => 'تاريخ البيع يجب أن يكون اليوم أو تاريخ سابق.',
        'usd_buy.min'      => 'سعر الشراء لا يمكن أن يكون سالبًا إلا في حالات الاسترداد أو الإلغاء.',
        'usd_sell.min'     => 'سعر البيع لا يمكن أن يكون سالبًا إلا في حالات الاسترداد أو الإلغاء.',
'amount_paid.min'  => 'المبلغ المدفوع لا يمكن أن يكون سالبًا.',
    ];

    public function toggleBuySellSigns()
    {
        if (is_numeric($this->usd_buy)) {
            $this->usd_buy *= -1;
        }
        if (is_numeric($this->usd_sell)) {
            $this->usd_sell *= -1;
        }
        $this->calculateProfit();
    }

    public function updatedCustomerId($value)
    {
        $customer = Customer::find($value);
        $this->customerLabel = $customer->name ?? '';
        $this->showCommission = $customer && $customer->has_commission;
        if (!$this->showCommission) {
            $this->commission = null;
            $this->commissionReadOnly = false;
        }
    }

    public function updatedProviderId($value)
    {
        $p = Provider::find($value);
        $this->providerLabel = $p->name ?? '';
    }

    public function save()
    {
    if ($this->isSaving) { return; }
    $this->isSaving = true;

    $lock = Cache::lock('sales:inflight:'.Auth::id(), 10);
    if (! $lock->get()) {
        $this->isSaving = false;
        $this->addError('general','طلب آخر قيد التنفيذ.');
        return;
    }

    try {
        // تطبيع قبل التحقق
        if ($this->payment_method === 'all') {
            $this->amount_paid = null; $this->payment_type = null;
            $this->receipt_number = null; $this->depositor_name = null;
        }
        if (in_array($this->status,['Refund-Full','Refund-Partial','Void'])) {
            $this->amount_paid = null;
        }

        $this->validate();

        if ($this->beneficiary_name && $this->phone_number) {
            $existing = Beneficiary::where('agency_id', Auth::user()->agency_id)
                ->where('phone_number', $this->phone_number)
                ->first();

            if (!$existing) {
                Beneficiary::create([
                    'agency_id' => Auth::user()->agency_id,
                    'name' => $this->beneficiary_name,
                    'phone_number' => $this->phone_number,
                ]);
            }
        }

       if ($this->payment_method === 'all') {
    $this->amount_paid = null; // اتركها null
}

       $sale = Sale::create([
            'beneficiary_name' => $this->beneficiary_name,
            'sale_date' => $this->sale_date,
            'service_type_id' => $this->service_type_id,
            'provider_id' => $this->provider_id,
            'customer_via' => $this->customer_via,
            'usd_buy' => $this->usd_buy,
            'usd_sell' => $this->usd_sell,
            'commission' => $this->commission,
            'route' => $this->route,
            'pnr' => $this->pnr,
            'reference' => $this->reference,
            'status' => $this->status,
            'amount_paid' => $this->amount_paid,
            'depositor_name' => $this->depositor_name,
            'customer_id' => $this->customer_id ?: null,
            'sale_profit' => $this->sale_profit,
            'payment_method' => $this->payment_method,
            'payment_type' => $this->payment_type,
            'receipt_number' => $this->receipt_number,
            'phone_number' => $this->phone_number,
            'user_id' => $this->original_user_id ?? Auth::id(),
            'duplicated_by' => $this->isDuplicated ? Auth::id() : null,
            'agency_id' => Auth::user()->agency_id,
            'service_date' => $this->service_date,
            'expected_payment_date' => $this->expected_payment_date,
            'sale_group_id' => $this->sale_group_id,
        ]);

// 1) إن كانت Refund: إيداع الاسترداد أولاً ثم تصفية الدين من الرصيد
if ($this->customer_id && (
    in_array($sale->status, ['Refund-Full','Refund-Partial']) || (float)$sale->usd_sell < 0
)) {
    app(\App\Services\CustomerCreditService::class)
        ->autoDepositToWallet(
            (int)$this->customer_id,
            Auth::user()->agency_id,
            'sales-auto|group:'.($sale->sale_group_id ?: $sale->id)
        );
}

// 2) مزامنة العمولة
if ($this->shouldSyncCommission($sale)) {
    app(\App\Services\CustomerCreditService::class)->syncCustomerCommission($sale);
}
// 2.1) تصفية شاملة من المحفظة لإطفاء الديون الأقدم أولاً
// 2.1) لا تصفِّ المحفظة إذا كانت العملية استرداد/سالب
if ($sale->customer_id) {
    $customer = \App\Models\Customer::where('agency_id', $sale->agency_id)->find($sale->customer_id);
    if ($customer && !in_array($sale->status, ['Refund-Full','Refund-Partial','Void']) && (float)$sale->usd_sell >= 0) {
        app(\App\Services\CustomerCreditService::class)->autoPayAllFromWallet($customer);
    }
}


// 3) أخيرًا: صَفِّ العملية الحالية إن تبقى عليها شيء
if (!str_contains(mb_strtolower((string)$sale->status), 'refund')) {
    app(\App\Services\CustomerCreditService::class)->autoPayFromWallet($sale);
}




// 🔔 بلّغ المحفظة لتتحدث فوراً
if ($this->customer_id) {
    $this->dispatch('wallet-updated', customerId: (int)$this->customer_id);
}

/* ✅ عمولة الموظّف (إضافة/تعديل الحركة المتوقعة حسب الهدف الشهري) */
$svc = app(\App\Services\EmployeeWalletService::class);
$ref = \Carbon\Carbon::parse($sale->sale_date ?: $sale->created_at);
$svc->recalcMonthForUser($sale->user_id, (int)$ref->year, (int)$ref->month);
// بديل أخف لو تحب لمس العملية فقط:
// app(\App\Services\EmployeeWalletService::class)->upsertExpectedCommission($sale);

if (in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void'])) {
    $this->amount_paid = 0;
}

$this->resetForm();
$this->isDuplicated = false;
$this->updateStatusOptions();
$this->status = 'Issued';
$this->successMessage = 'تمت إضافة العملية بنجاح';
$this->original_user_id = null;
} catch (\Illuminate\Validation\ValidationException $ve) {
    throw $ve; // دع Livewire يعرض رسائل الحقول
} catch (\Throwable $e) {
    report($e);
    $this->addError('general', 'تعذّر إتمام العملية الآن.');
} finally {
    // فكّ القفل فورًا حتى لا يظل محجوز 10 ثواني
    try { $lock->release(); } catch (\Throwable $e) {}
    $this->isSaving = false;
}
    }

    public function updatedPaymentMethod($value)
    {
        $this->showDepositorField = $value !== 'all';
        $this->showExpectedDate = in_array($value, ['part', 'all']);
        $this->showPaymentDetails = $value !== 'all';

        if ($value === 'kash') {
            $this->expected_payment_date = null;
        }

        if ($value === 'all') {
            $this->amount_paid = null;
            $this->payment_type = null;
            $this->receipt_number = null;
            $this->showCustomerField = true;
            $this->depositor_name = null;
       } elseif ($value === 'kash') {
            $this->showCustomerField = true;

            // لا تُخفِ العمولة أثناء التعديل. اسمح بالإخفاء فقط عند الإضافة/التكرار.
            if (!$this->isDuplicated && !$this->editingSale) {
                $this->commission = null;
                $this->showCommission = false;
            }
        } else {
            $this->showCustomerField = true;
        }
        if (!$this->isDuplicated) {
            $this->commissionReadOnly = false;
        }
    }

    public function applyFilters()
    {
        $this->filters = [
            'start_date' => $this->filterInputs['start_date'],
            'end_date' => $this->filterInputs['end_date'],
            'service_type_id' => $this->filterInputs['service_type_id'],
            'status' => $this->filterInputs['status'],
            'customer_id' => $this->filterInputs['customer_id'],
            'provider_id' => $this->filterInputs['provider_id'],
            'service_date' => $this->filterInputs['service_date'],
            'customer_via' => $this->filterInputs['customer_via'],
            'route' => $this->filterInputs['route'],
            'payment_method' => $this->filterInputs['payment_method'],
            'payment_type' => $this->filterInputs['payment_type'],
            'reference' => $this->filterInputs['reference'],
            'scope' => $this->filterInputs['scope'], // 👈
        ];
        $this->resetPage();
        $this->dispatch('filters-applied');
    }

    public function resetFilters()
    {
        $this->filters = [
            'start_date' => '',
            'end_date' => '',
            'service_type_id' => '',
            'status' => '',
            'customer_id' => '',
            'provider_id' => '',
            'service_date' => '',
            'customer_via' => '',
            'route' => '',
            'payment_method' => '',
            'payment_type' => '',
            'reference' => '',
            'scope' => 'mine', // 👈
        ];

        $this->filterInputs = $this->filters;
        $this->resetPage();
    }

    public function updatedStatus($value)
    {
        $this->updateShowCustomerField();

        if ($this->isDuplicated && in_array($value, ['Refund-Full', 'Refund-Partial', 'Void'])) {
            $this->showRefundModal = true;
            $this->payment_method = $this->payment_method;
$this->amount_paid = null; // اجعلها null في حالات Refund/Void
            $this->showAmountPaidField = false;
        } else {
            $this->showRefundModal = false;
            $this->showAmountPaidField = true;
        }

        $this->disablePaymentMethod = in_array($value, ['Refund-Full', 'Refund-Partial', 'Void']);

        if ($this->isDuplicated && $this->showCommission) {
            if ($value === 'Refund-Full') {
                $this->commission = 0;
                $this->commissionReadOnly = true;
            } elseif (!is_null($this->commission)) {
                $this->commissionReadOnly = true;
            } else {
                $this->commissionReadOnly = false;
            }
        } else {
            $this->commissionReadOnly = false;
        }
    }

    public function updatedPaymentType($value)
    {
        $this->updateShowCustomerField();
    }

    public function updateShowCustomerField()
    {
        if ($this->status === 'Re-Issued' && $this->payment_type === 'cash') {
            $this->showCustomerField = false;
        } else {
            $this->showCustomerField = true;
        }
    }

    public function openRefundModal()
    {
        $this->showRefundModal = true;
    }

    public function saveRefundValues()
    {
        if ($this->usd_buy > 0) {
            $this->usd_buy *= -1;
        }
        if ($this->usd_sell > 0) {
            $this->usd_sell *= -1;
        }
$this->amount_paid = null; // انسجاماً مع القاعدة الجديدة
        $this->calculateProfit();
        $this->showRefundModal = false;
        $this->updatedPaymentMethod($this->payment_method);
        $this->successMessage = 'تم تعديل المبالغ بنجاح';
    }

    public function getDisablePaymentMethodProperty()
    {
        return in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void']);
    }

    protected function onlyReferenceFilter()
    {
        $filters = $this->filters;
        $activeFilters = array_filter($filters, fn($v) => !empty($v));
        return count($activeFilters) === 1 && isset($activeFilters['reference']);
    }

    public function edit($id)
    {
        $sale = Sale::findOrFail($id);
        if ($this->remainingEditableMinutesFor($sale) <= 0) {
            $this->createEditApprovalRequest($sale);
            $this->addError('general', 'تم إرسال طلب تعديل لهذه العملية وبانتظار الموافقة. لا يمكنك التعديل الآن.');
            return;
        }
        $this->editingSale = $sale->id;

        $this->beneficiary_name = $sale->beneficiary_name;
        $this->sale_date = $sale->sale_date;
        $this->service_type_id = $sale->service_type_id;
        $this->provider_id = $sale->provider_id;
        $this->customer_via = $sale->customer_via;
        $this->usd_buy = $sale->usd_buy;
        $this->usd_sell = $sale->usd_sell;
        $this->commission = $sale->commission;
        $this->route = $sale->route;
        $this->pnr = $sale->pnr;
        $this->reference = $sale->reference;
        $this->status = $sale->status;
        $this->amount_paid = $sale->amount_paid;
        $this->depositor_name = $sale->depositor_name;
        $this->customer_id = $sale->customer_id;

        if ($sale->customer_id) {
            $c = \App\Models\Customer::find($sale->customer_id);
            $this->customerLabel = $c->name ?? '';
        }
        if ($sale->provider_id) {
            $p = \App\Models\Provider::find($sale->provider_id);
            $this->providerLabel = $p->name ?? '';
        }

        $this->formKey++;

        $customer = \App\Models\Customer::find($sale->customer_id);
        $this->showCommission = $customer && $customer->has_commission;
        if (!$this->showCommission) {
            $this->commission = null;
        }
        $this->payment_method = $sale->payment_method;
        $this->payment_type = $sale->payment_type;
        $this->receipt_number = $sale->receipt_number;
        $this->phone_number = $sale->phone_number;
        $this->service_date = $sale->service_date;
        $this->expected_payment_date = $sale->expected_payment_date;
        $this->sale_group_id = $sale->sale_group_id;

        $this->updatedPaymentMethod($this->payment_method);

        if ($this->payment_method === 'all') {
            $this->amount_paid         = null;
            $this->showAmountPaidField = false;
        }

        $this->isDuplicated = false;
        $this->updateStatusOptions();
        $this->calculateProfit();
        $this->calculateDue();
        $this->status = $sale->status;

        $this->dispatch('$refresh');
    }
    private function editWindowExpired(\App\Models\Sale $sale): bool
{
    $limit = $this->sale_edit_hours;
    return $limit > 0 && $sale->created_at->diffInHours(now()) >= $limit;
}
 

    public function update()
    {
    if ($this->isSaving) { return; }
    $this->isSaving = true;

    $lock = Cache::lock('sales:inflight:'.Auth::id(), 10);
    if (! $lock->get()) {
        $this->isSaving = false;
        $this->addError('general','طلب آخر قيد التنفيذ.');
        return;
    }

    try {
        // تطبيع قبل التحقق
        if ($this->payment_method === 'all') {
            $this->amount_paid = null; $this->payment_type = null;
            $this->receipt_number = null; $this->depositor_name = null;
        }
        if (in_array($this->status,['Refund-Full','Refund-Partial','Void'])) {
            $this->amount_paid = null;
        }

        $this->validate();

        $sale = Sale::findOrFail($this->editingSale);
        if ($this->remainingEditableMinutesFor($sale) <= 0) {
            $this->createEditApprovalRequest($sale);
            $this->addError('general', 'لا يمكن التحديث: انتهت/غير موجودة موافقة صالحة. تم إرسال طلب موافقة جديد.');
            return;
        }



        $sale->update([
            'beneficiary_name' => $this->beneficiary_name,
            'sale_date' => $this->sale_date,
            'service_type_id' => $this->service_type_id,
            'provider_id' => $this->provider_id,
            'customer_via' => $this->customer_via,
            'usd_buy' => $this->usd_buy,
            'usd_sell' => $this->usd_sell,
            'commission' => $this->commission,
            'route' => $this->route,
            'pnr' => $this->pnr,
            'reference' => $this->reference,
            'status' => $this->status,
            'amount_paid' => $this->amount_paid,
            'depositor_name' => $this->depositor_name,
            'customer_id' => $this->customer_id ?: null,
            'sale_profit' => $this->sale_profit,
            'payment_method' => $this->payment_method,
            'payment_type' => $this->payment_type,
            'receipt_number' => $this->receipt_number,
            'phone_number' => $this->phone_number,
            'service_date' => $this->service_date,
            'expected_payment_date' => $this->expected_payment_date,
            'updated_by' => Auth::id(),
        ]);


$sale->refresh();


// 1) إن كان Refund: أودِع أولاً ثم صفِّ الدين فوراً من الرصيد
if ($sale->customer_id && (
    in_array($sale->status, ['Refund-Full','Refund-Partial']) || (float)$sale->usd_sell < 0
)) {
    app(\App\Services\CustomerCreditService::class)
        ->autoDepositToWallet((int)$sale->customer_id, Auth::user()->agency_id, 'sales-auto|group:'.($sale->sale_group_id ?: $sale->id));
}

// 2) مزامنة العمولة
app(\App\Services\CustomerCreditService::class)->syncCustomerCommission($sale);

// 2.1) تصفية شاملة تسبق تصفية العملية الحالية
// 2.1) لا تصفِّ المحفظة إذا كانت العملية استرداد/سالب
if ($sale->customer_id) {
    $customer = \App\Models\Customer::where('agency_id', $sale->agency_id)->find($sale->customer_id);
    if ($customer && !in_array($sale->status, ['Refund-Full','Refund-Partial','Void']) && (float)$sale->usd_sell >= 0) {
        app(\App\Services\CustomerCreditService::class)->autoPayAllFromWallet($customer);
    }
}


// 3) تصفية العملية الحالية إن لزم
if (!str_contains(mb_strtolower((string)$sale->status), 'refund')) {
    app(\App\Services\CustomerCreditService::class)->autoPayFromWallet($sale);
}




// 4) (كما هو) إعادة حساب عمولات الموظف
$svc = app(\App\Services\EmployeeWalletService::class);
$ref = \Carbon\Carbon::parse($sale->sale_date ?: $sale->created_at);
$svc->recalcMonthForUser($sale->user_id, (int)$ref->year, (int)$ref->month);

// 5) تحديث واجهة المحفظة
if ($sale->customer_id) {
    $this->dispatch('wallet-updated', customerId: (int)$sale->customer_id);
}
 
        $this->resetForm();
        $this->editingSale = null;
        $this->successMessage = 'تم تحديث العملية بنجاح';
} catch (\Illuminate\Validation\ValidationException $ve) {
    throw $ve; // دع Livewire يعرض رسائل الحقول
} catch (\Throwable $e) {
    report($e);
    $this->addError('general', 'تعذّر إتمام العملية الآن.');
} finally {
    // فكّ القفل فورًا حتى لا يظل محجوز 10 ثواني
    try { $lock->release(); } catch (\Throwable $e) {}
    $this->isSaving = false;
}

    }

    public function showWallet(int $customerId): void
{
    // افتح مودال/صفحة المحفظة حسب تطبيقك
    $this->dispatch('open-wallet-modal', customerId: $customerId);

    // 👈 بلّغ مكوّن المحفظة ليتحدّث فورًا عند الفتح
    $this->dispatch('wallet-opened', customerId: $customerId)
         ->to(\App\Livewire\Agency\CustomerWallet::class);
}

// +++ ADD
private function shouldSyncCommission(\App\Models\Sale $sale): bool
{
    $st = mb_strtolower((string)($sale->status ?? ''));
    if (str_contains($st, 'refund') || $sale->status === 'Void') {
        return false; // لا عمولة على الاسترداد/الإلغاء
    }

    $groupId = $sale->sale_group_id;
    if (!$groupId) {
        // في حالة التكرار بدون group واضح: إن كان المنسوخ له عمولة، فلا تزامن
        return !($this->isDuplicated && (float)($this->commission ?? 0) > 0);
    }

    // إن كانت للمجموعة عمولة موجبة سابقة، فلا تزامن مرّة أخرى
    $exists = \App\Models\Sale::where('agency_id', $sale->agency_id)
        ->where('sale_group_id', $groupId)
        ->where('id', '!=', $sale->id)
        ->whereNotNull('commission')
        ->where('commission', '>', 0)
        ->exists();

    return !$exists;
}


}
