<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DetailsByServiceSheet implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private $byService, private string $currency) {}

    public function headings(): array
    {
        return [
            'الخدمة','عدد','البيع','الشراء','الربح',
            'عمولة الموظف (متوقعة)','عمولة الموظف (مستحقة)',
            'المستحق','العملة'
        ];
    }

    public function array(): array
    {
        return collect($this->byService)->map(function ($row) {
            return [
                $row['firstRow']?->service?->label ?? '—',
                (int)($row['count'] ?? 0),
                round((float)($row['sell'] ?? 0), 2),
                round((float)($row['buy'] ?? 0), 2),
                round((float)($row['profit'] ?? 0), 2),
                round((float)($row['employee_commission_expected'] ?? 0), 2),
                round((float)($row['employee_commission_due'] ?? 0), 2),
                round((float)($row['remaining'] ?? 0), 2),
                $this->currency,
            ];
        })->values()->all();
    }
}
