<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OperationsSheet implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private $sales, private string $currency) {}

    public function headings(): array
    {
        return [
            'تاريخ البيع','الخدمة','المزوّد','PNR','مرجع',
            'اسم المستفيد','البيع','الشراء','الربح','العمولة','المستحق','العملة'
        ];
    }

    public function array(): array
    {
        return collect($this->sales)->map(function ($s) {
            // جمع المدفوع لحساب المستحق
            $paid = in_array($s->status, ['Refund-Full','Refund-Partial']) ? 0
                  : (float)($s->amount_paid ?? 0) + (float)$s->collections->sum('amount');
            $remaining = (float)($s->usd_sell ?? 0) - $paid;

            return [
                optional($s->sale_date)->format('Y-m-d') ?? $s->sale_date,
                $s->service?->label,
                $s->provider?->name,
                $s->pnr,
                $s->reference,
                $s->beneficiary_name,
                round((float)$s->usd_sell, 2),
                round((float)$s->usd_buy, 2),
                round(((float)$s->usd_sell - (float)$s->usd_buy), 2),
                round((float)$s->commission, 2),
                round($remaining, 2),
                $this->currency,
            ];
        })->values()->all();
    }
}
