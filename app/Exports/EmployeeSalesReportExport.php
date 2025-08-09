<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeSalesReportExport implements WithMultipleSheets
{
    public function __construct(
        public ?int $employeeId,
        public string $currency,
        public Collection|array $perEmployee,
        public Collection|array $byService,
        public Collection|array $byMonth,
        public Collection|array $sales
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        if (!$this->employeeId) {
            // ملخص كل الموظفين في شيت واحد
            $sheets[] = new Sheets\EmployeesSummarySheet($this->perEmployee, $this->currency);
            return $sheets;
        }

        // موظف محدد: 3 شيتات
        $sheets[] = new Sheets\DetailsByServiceSheet($this->byService, $this->currency);
        $sheets[] = new Sheets\DetailsByMonthSheet($this->byMonth, $this->currency);
        $sheets[] = new Sheets\OperationsSheet(collect($this->sales), $this->currency);

        return $sheets;
    }
}
