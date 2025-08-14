<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;

class AccountsReportStreamExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        $q = Sale::query()->where('agency_id', Auth::user()->agency_id);

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $q->whereHas('user', fn($u)=>$u->where('name','like',"%$s%"));
        }
        if (!empty($this->filters['service_type'])) $q->where('service_type_id', $this->filters['service_type']);
        if (!empty($this->filters['provider']))     $q->where('provider_id', $this->filters['provider']);
        if (!empty($this->filters['account']))      $q->where('customer_id', $this->filters['account']);
        if (!empty($this->filters['start']))        $q->whereDate('sale_date','>=',$this->filters['start']);
        if (!empty($this->filters['end']))          $q->whereDate('sale_date','<=',$this->filters['end']);

        return $q->latest('sale_date');
    }

    public function headings(): array
    {
        return ['التاريخ','الموظف','الخدمة','المزوّد','حساب العميل','السعر بيع','السعر شراء','العمولة','الحالة'];
    }

    public function map($sale): array
    {
        return [
            \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d'),

            $sale->user?->name,
            $sale->serviceType?->label,
            $sale->provider?->name,
            $sale->customer?->name,
            $sale->usd_sell,
            $sale->usd_buy,
            $sale->commission,
            $sale->status,
        ];
    }
}
