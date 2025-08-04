<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountsReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected Collection $sales;

    public function __construct(array $data)
    {
        // تأكد أن sales عبارة عن Collection
        $this->sales = collect($data['sales'])->filter(fn($item) => $item instanceof Sale);
    }

    public function collection()
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return [
            'التاريخ',
            'العميل',
            'نوع الخدمة',
            'المزود',
            'المبلغ (USD)',
            'المرجع',
            'PNR',
        ];
    }

    public function map($sale): array
    {
        return [
            optional($sale->created_at)->format('Y-m-d'),
            optional($sale->customer)->name ?? '-',
            optional($sale->service)->label ?? '-',
            optional($sale->provider)->name ?? '-',
            number_format($sale->usd_sell, 2),
            $sale->reference ?? '-',
            $sale->pnr ?? '-',
        ];
    }
}
