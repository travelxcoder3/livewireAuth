<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Models\Sale;

class AccountsReportExport implements FromCollection, WithHeadings, WithMapping
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
            $sale->created_at?->format('Y-m-d'),
            $sale->customer->name ?? '',
            $sale->service->label ?? '',
            $sale->provider->name ?? '',
            $sale->usd_sell,
            $sale->reference,
            $sale->pnr,
        ];
    }
}
