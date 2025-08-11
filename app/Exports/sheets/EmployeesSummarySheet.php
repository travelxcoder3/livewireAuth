<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EmployeesSummarySheet implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private $perEmployee, private string $currency) {}

    public function headings(): array
    {
        return ['اسم الموظف','عدد العمليات','إجمالي البيع','إجمالي الشراء','الربح',   'عمولة الموظف (متوقعة)',
        'عمولة الموظف (مستحقة)','إجمالي المستحق','العملة'];
    }

    public function array(): array
    {
        return collect($this->perEmployee)->map(function ($row) {
            return [
                $row['user']?->name,
                $row['count'],
                round($row['sell'], 2),
                round($row['buy'], 2),
                round($row['profit'], 2),
                    round((float)($row['employee_commission_expected'] ?? 0), 2),
            round((float)($row['employee_commission_due'] ?? 0), 2),
                round($row['remaining'], 2),
                $this->currency,
            ];
        })->values()->all();
    }
}
