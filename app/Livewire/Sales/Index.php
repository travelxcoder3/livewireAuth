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
    'payment_type' => ''

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
    'payment_type' => ''
];

public $filterServices = [];
public $filterCustomers = [];
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
        $this->sale_profit = $sale->sale_profit;
        $this->payment_method = $sale->payment_method;
        $this->payment_type = $sale->payment_type;
        $this->receipt_number = $sale->receipt_number;
        $this->phone_number = $sale->phone_number;
        $this->service_date = $sale->service_date;
        $this->expected_payment_date = $sale->expected_payment_date;

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

        // تنظيف الحقول المحسوبة يدويًا
        $this->sale_profit = 0;
        $this->amount_due = 0;
        $this->showCommission = false;
        $this->showExpectedDate = false;
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


    public function render()
    {
        $user = Auth::user();
        $agency = $user->agency;

        if ($agency->parent_id) {
            if ($user->hasRole('agency-admin')) {
                // أدمن الفرع: يرى كل عمليات الفرع
                $userIds = $agency->users()->pluck('id')->toArray();
                $salesQuery = Sale::where('agency_id', $agency->id)
                                  ->whereIn('user_id', $userIds);
            } else {
                // مستخدم عادي في الفرع: يرى فقط عملياته
                $salesQuery = Sale::where('agency_id', $agency->id)
                                  ->where('user_id', $user->id);
            }
        } else {
            if ($user->hasRole('agency-admin')) {
                // أدمن الوكالة الرئيسية: يرى كل العمليات (الوكالة + الفروع)
                $branchIds = $agency->branches()->pluck('id')->toArray();
                $allAgencyIds = array_merge([$agency->id], $branchIds);
                $salesQuery = Sale::whereIn('agency_id', $allAgencyIds);
            } else {
                // مستخدم عادي في الوكالة الرئيسية: يرى فقط عملياته
                $salesQuery = Sale::where('agency_id', $agency->id)
                                  ->where('user_id', $user->id);
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
    ->when($this->filters['payment_type'], function($query) {
        $query->where('payment_type', $this->filters['payment_type']);
    });
        $sales = $salesQuery
            ->with(['user', 'provider', 'service', 'customer', 'account', 'collections'])
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

        $providers = $this->getFilteredProviders();
        $intermediaries = Intermediary::all();
        $customers = Customer::where('agency_id', Auth::user()->agency_id)->get();
        $accounts = Account::all();

        // إجمالي البيع = مجموع usd_sell
        $this->totalAmount = $salesQuery->sum('usd_sell');
        // المبلغ المحصل = amount_paid
        $this->totalReceived = $salesQuery->sum('amount_paid');
        // الآجل = إجمالي البيع - المحصل
       // جمع المدفوع المباشر + مجموع التحصيلات
$this->totalReceived = $salesQuery->sum('amount_paid') 
+ $salesQuery->withSum('collections', 'amount')->get()->sum('collections_sum_amount');

$this->totalPending = $this->totalAmount - $this->totalReceived;


        // الربح الإجمالي
        $this->totalProfit = $salesQuery->sum('sale_profit');

        $userSales = (clone $salesQuery)
            ->where('user_id', Auth::id())
            ->get();

        $totalProfit = $userSales->sum('sale_profit');

        // العمليات التي تم سدادها بالكامل
        $collectedProfit = $userSales->filter(function ($sale) {
            $collected = ($sale->amount_paid ?? 0) + $sale->collections->sum('amount');
            return $collected >= ($sale->usd_sell ?? 0);
        })->sum('sale_profit');

        $target = Auth::user()->main_target ?? 0;
        $rate = 0.17;

        $this->userCommission = max(($totalProfit - $target) * $rate, 0);
        $this->userCommissionDue = max(($collectedProfit - $target) * $rate, 0);

        return view('livewire.sales.index', [
            'sales' => $sales,
            'services' => $services,
            'providers' => $providers,
            'intermediaries' => $intermediaries,
            'customers' => $customers,
            'accounts' => $accounts,
            'filterServices' => $this->filterServices,
            'filterCustomers' => $this->filterCustomers,
            'columns' => SalesTable::columns(false, false),
        ])->layout('layouts.agency');
        $salesQuery = $salesQuery->with(['user', 'provider', 'service', 'customer', 'account', 'collections'])
    ->withSum('collections', 'amount')
    ->latest()
    ->paginate(10);

// إضافة خاصية محسوبة للوصول إليها في الجدول
$sales->each(function ($sale) {
    $sale->total_paid = ($sale->amount_paid ?? 0) + ($sale->collections_sum_amount ?? 0);
});
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
        if (is_numeric($this->usd_sell) && is_numeric($this->amount_paid)) {
            $this->amount_due = round($this->usd_sell - $this->amount_paid, 2);
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
        $this->currency = auth()->user()->agency->currency ?? 'USD';
        $this->sale_date = now()->format('Y-m-d');
        $this->fetchServices();
        $this->showExpectedDate = false;

        // تحميل البيانات للفلترة
        $this->filterServices = \App\Models\DynamicListItem::whereHas('list', function($query) {
            $query->where('name', 'قائمة الخدمات');
        })->pluck('label', 'id')->toArray();
        
        $this->filterCustomers = Customer::where('agency_id', auth()->user()->agency_id)
            ->pluck('name', 'id')
            ->toArray();
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
            'provider_id' => 'nullable|exists:providers,id',
            'customer_via' => 'nullable|in:whatsapp,facebook,instagram,call,office,other',
            'usd_buy' => 'required|numeric|min:0',
            'usd_sell' => 'required|numeric|min:0|gte:usd_buy',
            'commission' => 'nullable|numeric',
            'route' => 'required|string|max:255',
            'pnr' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:50',
            'amount_paid' => 'nullable|numeric|min:0',
            'depositor_name' => $this->payment_method !== 'all' ? 'required|string|max:255' : 'nullable',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_profit' => 'nullable|numeric',
            'receipt_number' => 'nullable|string|max:50',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:issued,refunded,canceled,pending,reissued,void,paid,unpaid',
            'payment_method' => 'required|in:kash,part,all',
            'payment_type' => $this->payment_method !== 'all' ? 'required|in:cash,transfer,account_deposit,fund,from_account,wallet,other' : 'nullable',            'service_date' => 'nullable|date',
            'expected_payment_date' => 'nullable|date',
        ];

        // قواعد إضافية حسب طريقة الدفع
        switch ($this->payment_method) {
            case 'kash':
                $rules['customer_id'] = 'nullable|exists:customers,id';
                $rules['amount_paid'] = ['required', 'numeric', function ($attribute, $value, $fail) {
                                                                        if (floatval($value) !== floatval($this->usd_sell)) {
                                                                            $fail('الدفع كاش، يشترط الدفع كامل.');
                                                                        }
                                                                    }];

                break;
            case 'part':
                $rules['customer_id'] = 'required';
                $rules['amount_paid'] = 'required|numeric|min:0|lt:' . $this->usd_sell;
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
            'amount_paid.lt' => 'المبلغ قليل.',
            'amount_paid.required' => 'أدخل المبلغ.',
            'amount_paid.prohibited' => 'احذف المبلغ.',
            'sale_date.before_or_equal' => 'تاريخ البيع يجب أن يكون اليوم أو تاريخ سابق.',
    ];


    public function updatedCustomerId($value)
    {
        $customer = Customer::find($value);
        $this->showCommission = $customer && $customer->has_commission;
        // إذا لم يكن للعميل عمولة، نفرغ حقل العمولة
        if (!$this->showCommission) {
            $this->commission = null;
        }
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
            'customer_id' => $this->customer_id,
            'sale_profit' => $this->sale_profit,
            'payment_method' => $this->payment_method,
            'payment_type' => $this->payment_type,
            'receipt_number' => $this->receipt_number,
            'phone_number' => $this->phone_number,
            'customer_via' => $this->customer_via,
            'user_id' => Auth::id(),
            'agency_id' => Auth::user()->agency_id,
            'service_date' => $this->service_date,
            'expected_payment_date' => $this->expected_payment_date,
        ]);

        $this->resetForm();
        $this->successMessage = 'تمت إضافة العملية بنجاح';
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
        $this->commission = null;
        $this->showCommission = false;
        $this->showCustomerField = true;
    } else {
        $this->showCustomerField = true;
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
        'payment_type' => $this->filterInputs['payment_type']
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
        'payment_type' => ''
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
        'payment_type' => ''
    ];
    
    $this->resetPage();
}
}
