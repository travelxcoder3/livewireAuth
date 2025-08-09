<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DetailsByMonthSheet implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private $byMonth, private string $currency) {}

    public function headings(): array
    {
        return ['الشهر','عدد','البيع','الشراء','الربح','العمولة','المستحق','العملة'];
    }

    public function array(): array
    {
        return collect($this->byMonth)->map(function ($row, $ym) {
            return [
                $ym,
                $row['count'],
                round($row['sell'], 2),
                round($row['buy'], 2),
                round($row['profit'], 2),
                round($row['commission'], 2),
                round($row['remaining'], 2),
                $this->currency,
            ];
        })->values()->all();
    }
}
