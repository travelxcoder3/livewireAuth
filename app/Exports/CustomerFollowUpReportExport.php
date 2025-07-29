<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Models\Sale;

class CustomerFollowUpReportExport implements FromCollection, WithHeadings, WithMapping
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
            'اسم المستفيد',
            'رقم هاتف المستفيد',
            'نوع الخدمة',
            'المسار/التفاصيل',
            'تاريخ الخدمة',
            'حساب العميل',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->beneficiary_name ?? '',
            $sale->phone_number ?? '',
            $sale->service->label ?? '',
            $sale->route ?? '',
            $sale->service_date ? \Carbon\Carbon::parse($sale->service_date)->format('Y-m-d') : '',
            $sale->customer->name ?? '',
        ];
    }
}
