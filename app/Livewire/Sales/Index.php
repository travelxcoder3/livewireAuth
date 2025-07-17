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

class Index extends Component
{
    use WithPagination;

    public $beneficiary_name, $sale_date, $provider_id,
        $customer_via, $usd_buy, $usd_sell, $commission, $route, $pnr, $reference,
        $status, $amount_paid, $depositor_name, $account_id, $customer_id, $sale_profit = 0,
        $payment_method, $payment_type, $receipt_number, $phone_number, $service_type_id;


    public $showCommission = false;
    public $editingSale = null;
    public $currency;
    public $totalAmount = 0;           // إجمالي البيع
    public $totalReceived = 0;         // ما تم تحصيله
    public $totalPending = 0;          // المبالغ الآجلة
    public $totalProfit = 0;           // إجمالي الربح
    public $amount_due = 0; // المبلغ المتبقي
    public $services = []; // أضف هذا الخاصية

    // في دالة mount أو مكان مناسب
    public function fetchServices()
    {
        $this->services = \App\Models\DynamicListItem::whereHas('list', function ($query) {
            $query->where('name', 'قائمة الخدمات');
        })->get();
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
            'phone_number'
        ]);
    }

    public function resetFields()
    {
        $this->resetForm();
    }
    public function updatedServiceTypeId()
    {
        $this->provider_id = null;
    }

    public function getFilteredProviders()
    {
        return Provider::query()
            ->where('agency_id', Auth::user()->agency_id)
            ->where('status', 'approved') // فقط المزودين المعتمدين
            ->when(
                $this->service_type_id,
                fn($q) =>
                $q->where('service_item_id', $this->service_type_id)
            )
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

        $sales = $salesQuery
            ->with(['user', 'provider', 'service', 'customer', 'account'])
            ->latest()
            ->paginate(10);

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
        $this->totalPending = $this->totalAmount - $this->totalReceived;
        // الربح الإجمالي
        $this->totalProfit = $salesQuery->sum('sale_profit');

        return view('livewire.sales.index', [
            'sales' => $sales,
            'services' => $services,
            'providers' => $providers,
            'intermediaries' => $intermediaries,
            'customers' => $customers,
            'accounts' => $accounts
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
    }

    protected function rules()
    {
        $rules = [
            'beneficiary_name' => 'required|string|max:255',
            'sale_date' => 'required|date',
            'service_type_id' => 'required|exists:dynamic_list_items,id',
            'provider_id' => 'nullable|exists:providers,id',
            'customer_via' => 'nullable|in:whatsapp,viber,instagram,other',
            'usd_buy' => 'required|numeric|min:0',
            'usd_sell' => 'required|numeric|min:0|gte:usd_buy',
            'commission' => 'nullable|numeric',
            'route' => 'required|string|max:255',
            'pnr' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:50',
            'amount_paid' => 'nullable|numeric|min:0',
            'depositor_name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_profit' => 'nullable|numeric',
            'receipt_number' => 'nullable|string|max:50',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:issued,refunded,canceled,pending,reissued,void,paid,unpaid',
            'payment_method' => 'required|in:kash,part,all',
            'payment_type' => 'required|in:creamy,kash,visa',
        ];

        // قواعد إضافية حسب طريقة الدفع
        switch ($this->payment_method) {
            case 'kash':
                $rules['customer_id'] = 'prohibited';
               $rules['amount_paid'] = ['required', 'numeric', function ($attribute, $value, $fail) {
                                                                        if (floatval($value) !== floatval($this->usd_sell)) {
                                                                            $fail('عند الدفع كاش، يجب دفع المبلغ كاملًا.');
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
        'usd_sell.gte' => 'يجب أن يكون سعر البيع أكبر أو يساوي سعر الشراء',
        'customer_id.prohibited' => 'لا يمكن تحديد حساب عند الدفع كاش.',
        'amount_paid.max' => 'يجب أن يكون المبلغ المدفوع مساويًا أو أقل من قيمة البيع.',
        'customer_id.required' => 'يجب تحديد حساب عند الدفع الجزئي أو على الحساب.',
        'amount_paid.lt' => 'المبلغ الجزئي يجب أن يكون أقل من قيمة البيع.',
        'amount_paid.required' => 'يجب إدخال المبلغ المدفوع عند الدفع الجزئي.',
        'amount_paid.prohibited' => 'لا يجب إدخال مبلغ مدفوع عند الدفع على الحساب.',
    ];

    public function updatedCustomerId($value)
    {
        $customer = Customer::find($value);
        $this->showCommission = $customer && $customer->has_commission;
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
        ]);

        $this->resetForm();
        $this->successMessage = 'تمت إضافة العملية بنجاح';
    }

}
