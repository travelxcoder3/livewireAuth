<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CustomerSalesReportExport implements FromView
{
    public function __construct(
        public ?int $customerId,
        public string $currency,
        public $perCustomer,
        public $byService,
        public $byMonth,
        public $sales,
    ) {}

    public function view(): View
    {
        return view('reports.customer-sales', [
            'currency'    => $this->currency,
            'perCustomer' => $this->perCustomer,
            'byService'   => $this->byService,
            'byMonth'     => $this->byMonth,
            'sales'       => $this->sales,
            'customerId'  => $this->customerId,
        ]);
    }
}
