<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;

class SalesExport implements FromCollection, WithHeadings
{
    protected $fields;
    protected $startDate;
    protected $endDate;

    public function __construct($fields = null, $startDate = null, $endDate = null)
    {
        $this->fields = $fields;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->currency = Auth::user()->agency->currency ?? 'USD';

    }

public function collection()
{
    $user = Auth::user();
    $query = Sale::with(['customer', 'serviceType', 'provider', 'user', 'account'])
        ->where('agency_id');

    if ($this->startDate && $this->endDate) {
        $query->whereBetween('sale_date', [$this->startDate, $this->endDate]);
    }

    $sales = $query->latest()->get();

    return $sales->map(function ($sale) {
        $fieldMap = [
            'sale_date' => $sale->sale_date,
            'user' => optional($sale->user)->name,
            'beneficiary_name' => $sale->beneficiary_name,
            'phone_number' => $sale->phone_number,
            'serviceType' => optional($sale->serviceType)->label,
            'provider' => optional($sale->provider)->name,
            'usd_buy' => $sale->usd_buy,
            'usd_sell' => $sale->usd_sell,
            'sale_profit' => $sale->sale_profit,
            'amount_paid' => $sale->amount_paid,
            'commission' => $sale->commission,
            'depositor_name' => $sale->depositor_name,
            'route' => $sale->route,
            'reference' => $sale->reference,
            'pnr' => $sale->pnr,
            'customer' => optional($sale->customer)->name,
            'customer_via' => match ($sale->customer_via) {
                'facebook' => 'فيسبوك',
                'call' => 'اتصال',
                'instagram' => 'إنستغرام',
                'whatsapp' => 'واتساب',
                'office' => 'عبر مكتب',
                'other' => 'أخرى',
                default => $sale->customer_via,
            },

            'payment_method' => match ($sale->payment_method) {
                'kash' => 'كامل',
                'part' => 'جزئي',
                'all' => 'لم يدفع',
                default => $sale->payment_method,
            },

            'payment_type' => match ($sale->payment_type) {
                'cash' => 'كاش',
                'transfer' => 'حوالة',
                'account_deposit' => 'إيداع حساب',
                'fund' => 'صندوق',
                'from_account' => 'من حساب',
                'wallet' => 'محفظة',
                'other' => 'أخرى',
                default => $sale->payment_type,
            },

            'receipt_number' => $sale->receipt_number,
            'service_date' => $sale->service_date,
            'expected_payment_date' => $sale->expected_payment_date,
            'status' => $sale->status,
        ];

        if ($this->fields) {
            return collect($this->fields)->map(fn($key) => $fieldMap[$key] ?? '')->toArray();
        }

        return array_values($fieldMap);
    });
}


    public function headings(): array
{
    $headingsMap = [
        'sale_date' => 'تاريخ البيع',
        'user' => 'اسم الموظف',
        'beneficiary_name' => 'اسم المستفيد',
        'phone_number' => 'رقم الهاتف',
        'serviceType' => 'نوع الخدمة',
        'provider' => 'المزود',
        'usd_buy' => 'سعر الشراء (' . $this->currency . ')',
        'usd_sell' => 'سعر البيع (' . $this->currency . ')',
        'sale_profit' => 'الربح',
        'amount_paid' => 'المبلغ المدفوع',
        'commission' => 'العمولة',
        'depositor_name' => 'اسم المودع',
        'route' => 'Route',
        'reference' => 'الرقم المرجعي',
        'pnr' => 'PNR',
        'customer' => 'العميل',
        'customer_via' => 'العميل عبر',
        'payment_method' => 'حالة الدفع',
        'payment_type' => 'وسيلة الدفع',
        'receipt_number' => 'رقم السند',
        'service_date' => 'تاريخ الخدمة',
        'expected_payment_date' => 'تاريخ السداد المتوقع',
        'status' => 'الحالة',
    ];

    if (!$this->fields) {
        $this->fields = array_keys($headingsMap);
    }

    return collect($this->fields)->map(fn($key) => $headingsMap[$key] ?? $key)->toArray();
}
    
}