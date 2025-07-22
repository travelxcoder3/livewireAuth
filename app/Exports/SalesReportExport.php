<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Models\Sale;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $sales;

    public function __construct(array $data)
    {
        $this->sales = $data['sales'];
    }

    public function collection()
    {
        return collect($this->sales)->filter(fn($item) => $item instanceof Sale);
    }

    public function headings(): array
    {
        return [
            'التاريخ',
            'الموظف المسؤول',
            'نوع الخدمة',
            'المزود',
            'حساب العميل',
            'المبلغ (USD)',
            'المرجع',
            'PNR',
            'العميل عبر',
            'طريقة الدفع',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->created_at?->format('Y-m-d'),
            $sale->user->name ?? '', // الموظف المسؤول
            $sale->service->label ?? $sale->serviceType->label ?? '', // نوع الخدمة
            $sale->provider->name ?? '', // المزود
            $sale->customer->name ?? '', // حساب العميل
            $sale->usd_sell,
            $sale->reference,
            $sale->pnr,
            $sale->customer_via ?? '', // العميل عبر
            $sale->payment_method ?? '', // طريقة الدفع
        ];
    }
}
