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
    public $totalAmount = 0;           // إجمالي البيع
    public $totalReceived = 0;         // ما تم تحصيله
    public $totalPending = 0;          // المبالغ الآجلة
    public $totalProfit = 0;           // إجمالي الربح
    public $amount_due = 0; // المبلغ المتبقي
    public $services = []; // أضف هذا الخاصية
    public $showExpectedDate = false;
    public $showCustomerField = true;
    public $showPaymentDetails = true;
    public $showDepositorField = true;
    public ?string $sale_group_id = null;
    public bool $isDuplicated = false; // تم التكرار
    public bool $showRefundModal = false; // لعرض واجهة تعديل المبالغ
    public bool $showAmountPaidField = true;
    public bool $disablePaymentMethod = false;
    public bool $commissionReadOnly = false;
    public array $statusOptions = [];
    public $original_user_id = null;


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
    'reference' => ''
];

// بيانات النموذج المؤقت داخل نافذة الفلترة
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
    'reference' => ''
];

public $filterServices = [];
public $filterCustomers = [];
// ابحث عن مكان الخصائص وضع التالي
public string $customerSearch = '';
public array  $customerOptions = [];

public string $providerSearch = '';
public array  $providerOptions = [];
public string $customerLabel = '';
public string $providerLabel = '';
public int $formKey = 0;

// اجلب أول 20 نتيجة حسب البحث
public function refreshCustomerOptions()
{
    $term = trim($this->customerSearch);

    $this->customerOptions = \App\Models\Customer::query()
        ->where('agency_id', auth()->user()->agency_id)
        ->when($term !== '', function ($q) use ($term) {
            $len  = mb_strlen($term);
            $like = $len >= 3 ? "%{$term}%" : "{$term}%";   // contains من 3 أحرف
            $q->where('name', 'like', $like);
        })
        ->select('id','name')                                // حمولة أخف
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
            $like = $len >= 3 ? "%{$term}%" : "{$term}%";   // contains من 3 أحرف
            $q->where('name', 'like', $like);
        })
        ->select('id','name')                                // حمولة أخف
        ->orderBy('name')
        ->limit(20)
        ->pluck('name', 'id')
        ->toArray();
}




// تحدّث عند الكتابة في صندوق البحث
public function updatedCustomerSearch()
{
    $len = mb_strlen($this->customerSearch);
    if ($len === 0 || $len >= 2) {      // تقدر تخليها 3 لو تبغى أهدى
        $this->refreshCustomerOptions();
    }
    $this->skipRender();
}

public function updatedProviderSearch()
{
    $len = mb_strlen($this->providerSearch);
    if ($len === 0 || $len >= 2) {      // أو 3
        $this->refreshProviderOptions();
    }
    $this->skipRender();
}

    // في دالة mount أو مكان مناسب
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
        $this->original_user_id = $sale->user_id; // حفظ صاحب العملية الأصلية
        $this->updateStatusOptions(); // ✅ توليد قائمة الحالات الجديدة المسموح بها

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

            // ❗ شرط التصفير فقط إذا كانت الحالة Refund-Full
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
        $this->amount_paid = $sale->amount_paid;
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

        // معالجة الحقول الشرطية يدويًا
        $this->showExpectedDate = in_array($sale->payment_method, ['part', 'all']);

        $customer = \App\Models\Customer::find($sale->customer_id);
        $this->showCommission = $customer && $customer->has_commission;

        //  إعادة حساب القيم المحسوبة
        $this->calculateProfit();
        $this->calculateDue();
// معالجة الحقول الشرطية يدويًا
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

// 🟡 تصفير الحقول غير المرئية حسب نوع الدفع
if ($sale->payment_method === 'all') {
    $this->amount_paid = null;
}

$this->status = null; // لتفريغ الحقل وإعادة توليد القائمة
$this->dispatch('$refresh'); // لإجبار Livewire على إعادة تنفيذ getStatusOptionsProperty



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

    $this->showAmountPaidField = true;
    $this->disablePaymentMethod = false;

    // تنظيف الحقول المحسوبة يدويًا
    $this->sale_profit = 0;
    $this->amount_due = 0;
    $this->showCommission = false;
    $this->commission = null;
    $this->commissionReadOnly = false;
    $this->showExpectedDate = false;
    $this->sale_group_id  = Str::uuid();

    $this->isDuplicated = false;
    $this->updateStatusOptions();
    $this->editingSale = null;

    // ✅ امسح اللّيبلز تمامًا
    $this->customerLabel = '';
    $this->providerLabel = '';

    // ✅ امسح كلمات البحث وأعد تحميل أول 20 خيار
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
    // استبعاد الحالتين "تم الإصدار" و"قيد التقديم" عند التكرار
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

        // ✅ حالة استثناء: إذا تم إدخال الرقم المرجعي فقط
        if (!empty($this->filters['reference']) && $this->onlyReferenceFilter()) {
            // لا تقيد بـ user_id
        } else {
            $salesQuery->where('user_id', $user->id); // التقييد الطبيعي
        }
    }
} else {
    if ($user->hasRole('agency-admin')) {
        $branchIds = $agency->branches()->pluck('id')->toArray();
        $allAgencyIds = array_merge([$agency->id], $branchIds);
        $salesQuery = Sale::whereIn('agency_id', $allAgencyIds);
    } else {
        $salesQuery = Sale::where('agency_id', $agency->id);

        // ✅ حالة استثناء: إذا تم إدخال الرقم المرجعي فقط
        if (!empty($this->filters['reference']) && $this->onlyReferenceFilter()) {
            // لا تقيد بـ user_id
        } else {
            $salesQuery->where('user_id', $user->id); // التقييد الطبيعي
        }
    }
}

    // تطبيق الفلترة
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
    ->when($this->filters['reference'], function($query) {
        $query->where('reference', 'like', '%'.$this->filters['reference'].'%');
    })

    ->when($this->filters['payment_type'], function($query) {
        $query->where('payment_type', $this->filters['payment_type']);
    });
$totalsQuery = (clone $salesQuery); // ← أساس الحسابات بدون ترقيم صفحات

    $sales = (clone $salesQuery)
    ->with(['user','provider','service','customer','account','collections','updatedBy','duplicatedBy'])
    ->withSum('collections', 'amount')
    ->latest()
    ->paginate(10);

        $sales->each(function ($sale) {
            $sale->total_paid = ($sale->amount_paid ?? 0) + ($sale->collections_sum ?? 0);
        });
        $sales->each(function ($sale) {
    $sale->total_paid = ($sale->amount_paid ?? 0) + ($sale->collections_sum_amount ?? 0);
    $sale->remaining_payment = ($sale->usd_sell ?? 0) - $sale->total_paid;
});



        $services = \App\Models\DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة الخدمات');
        })->get();

        $intermediaries = Intermediary::all();
        $accounts = Account::all();

// ====== إجماليات الموظف المسجّل دخول (على مستوى الجدول كامل) ======
// استخدام نفس الفلاتر، تقييد بـ Auth::id()، واستثناء الملغى
$employeeRows = (clone $salesQuery)
    ->where('user_id', Auth::id())
    ->where('status', '!=', 'Void')
    ->with('collections')
    ->get();

// التجميع حسب المجموعة (أو id إن لم توجد مجموعة)
$grouped = $employeeRows->groupBy(fn($s) => $s->sale_group_id ?: $s->id);

$totalAmount          = 0.0; // صافي المبيعات بعد الاستردادات داخل المجموعة
$totalReceived        = 0.0; // المحصل (مقيد بألا يتجاوز الصافي)
$totalPending         = 0.0; // غير المحصل
$totalProfit          = 0.0; // الربح الإجمالي
$totalCollectedProfit = 0.0; // الربح المُحقق (تحصيل كامل للمجموعة)

foreach ($grouped as $group) {
    $netSell = (float) $group->sum('usd_sell');
    if ($netSell <= 0) {
        continue;
    }

    $netCollected = (float) $group->sum('amount_paid')
        + (float) $group->pluck('collections')->flatten()->sum('amount');

    $groupProfit = (float) $group->sum('sale_profit');

    $totalAmount   += $netSell;
    $totalReceived += min($netCollected, $netSell);
    $totalProfit   += $groupProfit;

    // يعتبر مُحققًا عند التحصيل الكامل (سماحية سنت)
    if ($netCollected + 0.01 >= $netSell) {
        $totalCollectedProfit += $groupProfit;
    }
}

$totalPending = max($totalAmount - $totalReceived, 0);

// تمرير للإستعمال في الواجهة
$this->totalAmount   = $totalAmount;
$this->totalReceived = $totalReceived;
$this->totalPending  = $totalPending;
$this->totalProfit   = $totalProfit;



        // الربح الإجمالي
  // العمولات: المقدّرة على كامل الربح، والمستحقة على الربح المُحقق فقط
$target = (float) (Auth::user()->main_target ?? 0);
$rate   = 0.17;

$this->userCommission    = max(($totalProfit - $target) * $rate, 0);            // عمولة مقدّرة
$this->userCommissionDue = max(($totalCollectedProfit - $target) * $rate, 0);   // عمولة مستحقة
return view('livewire.sales.index', [
    'sales'           => $sales,
    'services'        => $services,
    'intermediaries'  => $intermediaries,
    'accounts'        => $accounts,

    // خيارات خفيفة تُبنى حسب البحث
    'providerOptions' => $this->providerOptions,
    'customerOptions' => $this->customerOptions,

    'filterServices'  => $this->filterServices,
    'filterCustomers' => $this->filterCustomers, // ممكن نحذفها لاحقًا لو ما عادت تُستخدم
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
        // لو "لم يدفع" أو المبلغ المدفوع فاضي، اعتبر المدفوع = 0 واطبع المتبقي = مبلغ البيع
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
    public $successMessage;

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

    // فلاتر الخدمات فقط (كما هي)
    $this->filterServices = \App\Models\DynamicListItem::whereHas('list', function($query) {
        $query->where('name', 'قائمة الخدمات');
    })->pluck('label', 'id')->toArray();

    // تهيئة خيارات العملاء والمزودين (خفيفة)
    $this->refreshCustomerOptions();
    $this->refreshProviderOptions();

    // لم نعد نحتاج تحميل كل العملاء هنا
    $this->filterCustomers = []; // اختياري: اتركه فارغ
    if ($this->customer_id) {
    $c = Customer::find($this->customer_id);
    $this->customerLabel = $c->name ?? '';
}
if ($this->provider_id) {
    $p = Provider::find($this->provider_id);
    $this->providerLabel = $p->name ?? '';
}

}

    protected function getListeners()
    {
        return [
            'payment-collected' => 'refreshSales',
        ];
    }
    public function refreshSales()
    {
        $this->render(); // إعادة تحميل البيانات عند استلام حدث تحصيل جديد
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
        if (in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void'])) {
            if ($value >= 0) {
                $fail('المبلغ المسترد يجب أن يكون سالبًا.');
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
            'payment_type' => $this->payment_method !== 'all' ? 'required|in:cash,transfer,account_deposit,fund,from_account,wallet,other' : 'nullable',            'service_date' => 'required|date',
            
            
            'expected_payment_date' => 'nullable|date',
        ];

        // قواعد إضافية حسب طريقة الدفع
        switch ($this->payment_method) {
            case 'kash':
                $rules['customer_id'] = 'nullable|exists:customers,id';
                $rules['amount_paid'] = ['required', 'numeric', function ($attribute, $value, $fail) {
                    // ✅ في حالة الاسترداد، السماح بأي مبلغ (حتى 0 أو سالب)
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

            // ✅ السماح بأي مبلغ في حالة الاسترداد أو الإلغاء
            if (in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void'])) {
                return;
            }

            // ⚠️ في الحالة العادية، المبلغ المدفوع يجب أن يكون أقل من البيع
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
'amount_paid.min'  => 'المبلغ المدفوع لا يمكن أن يكون سالبًا إلا في حالات الاسترداد أو الإلغاء.',
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
        
        $this->validate();

        // التأكد من وجود مستفيد بنفس الرقم داخل نفس الوكالة
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

        // إذا كانت طريقة الدفع "كامل جزئي" نجبر المبلغ المدفوع على الصفر
        if ($this->payment_method === 'all') {
            $this->amount_paid = 0;
        }

        Sale::create([
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
            'customer_via' => $this->customer_via,
            'user_id' => $this->original_user_id ?? Auth::id(),
            'duplicated_by' => $this->isDuplicated ? Auth::id() : null,
            'agency_id' => Auth::user()->agency_id,
            'service_date' => $this->service_date,
            'expected_payment_date' => $this->expected_payment_date,
            'sale_group_id' => $this->sale_group_id, // ✅ نستخدم القيمة المخزنة بدون تغيير
            
        ]);
if (in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void'])) {
    $this->amount_paid = 0;
}

        $this->resetForm();
        $this->isDuplicated = false; // ✅ العودة للوضع الطبيعي بعد الحفظ
        $this->updateStatusOptions(); // ✅ توليد خيارات الحالة الافتراضية
        $this->status = 'Issued'; // ✅ إعادة الحالة الافتراضية تلقائيًا
        $this->successMessage = 'تمت إضافة العملية بنجاح';
        $this->original_user_id = null;
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
    if (!$this->isDuplicated) {
        $this->commission = null;
        $this->showCommission = false;
    }
    $this->showCustomerField = true;
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
    ];
    $this->resetPage(); // إعادة تعيين الصفحة عند تطبيق الفلترة
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
    ];
    
    $this->filterInputs = [
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
    ];
    
    $this->resetPage();
}

// في المكان المناسب بعد تحديث status أو payment_type
public function updatedStatus($value)
{
    $this->updateShowCustomerField();

    if ($this->isDuplicated && in_array($value, ['Refund-Full', 'Refund-Partial', 'Void'])) {
        $this->showRefundModal = true;

        $this->payment_method = $this->payment_method;
        $this->amount_paid = 0;

        // ✅ إخفاء حقل المبلغ المدفوع
        $this->showAmountPaidField = false;
    } else {
        $this->showRefundModal = false;

        // ✅ إظهار الحقل في الحالات العادية
        $this->showAmountPaidField = true;
    }

     // ✅ تفعيل/تعطيل حقل حالة الدفع بناءً على الحالة
    $this->disablePaymentMethod = in_array($value, ['Refund-Full', 'Refund-Partial', 'Void']);

    // 🔄 تحديث حالة قراءة حقل العمولة عند تغيير الحالة
    if ($this->isDuplicated && $this->showCommission) {
        if ($value === 'Refund-Full') {
            $this->commission = 0;
            $this->commissionReadOnly = true;
        } elseif (!is_null($this->commission)) {
            $this->commissionReadOnly = true;
        } else {
            $this->commissionReadOnly = false;
        }
    }
    else {
    // ✅ في الوضع العادي (مش تكرار) افتح الحقل
    $this->commissionReadOnly = false;
}

}



public function updatedPaymentType($value)
{
    $this->updateShowCustomerField();
}

public function updateShowCustomerField()
{
    // إخفاء الحقل فقط إذا كانت الحالة Re-Issued ووسيلة الدفع كاش
    if ($this->status === 'Re-Issued' && $this->payment_type === 'cash') {
        $this->showCustomerField = false;
    } else {
        $this->showCustomerField = true;
    }
}

public function openRefundModal()
{
    // فتح النافذة فقط يدويًا
    $this->showRefundModal = true;
}

public function saveRefundValues()
{
    // ✅ اجعل المبالغ سالبة للتأكد
    if ($this->usd_buy > 0) {
        $this->usd_buy *= -1;
    }

    if ($this->usd_sell > 0) {
        $this->usd_sell *= -1;
    }

    // ✅ تصفير المبلغ المدفوع
    $this->amount_paid = 0;

    // ✅ إعادة حساب الربح
    $this->calculateProfit();

    // ✅ إغلاق النافذة
    $this->showRefundModal = false;

    // ✅ تحديث واجهة الحقول
    $this->updatedPaymentMethod($this->payment_method);

    // ✅ إعطاء رسالة مؤقتة مثلاً
    $this->successMessage = 'تم تعديل المبالغ بنجاح';
}

public function getDisablePaymentMethodProperty()
{
    return in_array($this->status, ['Refund-Full', 'Refund-Partial', 'Void']);
}
protected function onlyReferenceFilter()
{
    $filters = $this->filters;

    // أزل الحقول الفارغة من الفلاتر
    $activeFilters = array_filter($filters, fn($v) => !empty($v));

    // هل المرجع هو الفلتر الوحيد؟
    return count($activeFilters) === 1 && isset($activeFilters['reference']);
}

public function edit($id)
{
    $sale = Sale::findOrFail($id);
    $this->editingSale = $sale->id;

    // نفس الحقول في duplicate()
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
    
    /**  توحيد حالة الواجهة كما نفعل عند تغيير الـ payment_method */
    $this->updatedPaymentMethod($this->payment_method);

    /**  عند "لم يدفع" لا نعرض قيمة 0.0 إطلاقًا */
    if ($this->payment_method === 'all') {
        $this->amount_paid         = null;   // لا تُظهر 0.0
        $this->showAmountPaidField = false;  // أخفِ الحقل كليًا
    }

    // خصائص مساعدة
    $this->isDuplicated = false;
    $this->updateStatusOptions();
    $this->calculateProfit();
    $this->calculateDue();

    // عرض الحالة كما هي
    $this->status = $sale->status;

    // ✅ غير قابل للتعديل إذا مر الوقت
    $this->dispatch('$refresh');
}
public function update()
{
    $this->validate();

    $sale = Sale::findOrFail($this->editingSale);

    // تأكد لم تمر 3 ساعات
    if ($sale->created_at->diffInHours(now()) >= 3) {
        $this->addError('general', 'لا يمكن تعديل العملية بعد مرور 3 ساعات.');
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

    $this->resetForm();
    $this->editingSale = null;
    $this->successMessage = 'تم تحديث العملية بنجاح';
}
}
