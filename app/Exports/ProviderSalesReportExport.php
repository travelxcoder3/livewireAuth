<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProviderSalesReportExport implements FromView
{
    public function __construct(
        public ?int $providerId,
        public string $currency,
        public $perProvider,
        public $byService,
        public $byMonth,
        public $sales,
    ) {}

    public function view(): View
    {
        return view('reports.provider-sales', [
            'currency'    => $this->currency,
            'perProvider' => $this->perProvider,
            'byService'   => $this->byService,
            'byMonth'     => $this->byMonth,
            'sales'       => $this->sales,
            'providerId'  => $this->providerId,
        ]);
    }
}
