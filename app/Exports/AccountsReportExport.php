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
    protected string $currency;


    public function __construct(array $data)
    {
        $this->sales = collect($data['sales'])->filter(fn($item) => $item instanceof Sale);
        $this->currency = $data['currency'] ?? 'USD';
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
            "المبلغ ({$this->currency})",
            'المرجع',
            'PNR',
        ];
    }

    public function map($sale): array
    {
        $amount = match ($this->currency) {
            'USD' => $sale->usd_sell,
            'SAR' => $sale->sar_sell ?? $sale->usd_sell,
            default => $sale->usd_sell,
        };

        return [
            optional($sale->created_at)?->format('Y-m-d'),
            optional($sale->customer)?->name ?? '-',
            optional($sale->service)?->label ?? '-',
            optional($sale->provider)?->name ?? '-',
            number_format($amount, 2),
            $sale->reference ?? '-',
            $sale->pnr ?? '-',
        ];
    }
}
