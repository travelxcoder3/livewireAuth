<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;

class AccountsExport implements FromCollection, WithHeadings
{
    protected $fields;
    protected $filters;

    public function __construct($fields = null, $filters = [])
    {
        $this->fields = $fields;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Sale::with(['customer', 'serviceType', 'provider', 'intermediary', 'account', 'user'])
            ->where('agency_id', Auth::user()->agency_id);

        // تطبيق الفلاتر
        if (!empty($this->filters)) {
            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('sale_date', [$this->filters['start_date'], $this->filters['end_date']]);
            }

            if (!empty($this->filters['service_type'])) {
                $query->where('service_type_id', $this->filters['service_type']);
            }

            if (!empty($this->filters['provider'])) {
                $query->where('provider_id', $this->filters['provider']);
            }

            if (!empty($this->filters['account'])) {
                $query->where('account_id', $this->filters['account']);
            }

            if (!empty($this->filters['pnr'])) {
                $query->where('pnr', 'like', '%' . $this->filters['pnr'] . '%');
            }

            if (!empty($this->filters['reference'])) {
                $query->where('reference', 'like', '%' . $this->filters['reference'] . '%');
            }
            if (!empty($this->filters['user_id'])) {
                $query->where('user_id', $this->filters['user_id']);
            }
        }

        $sales = $query->latest()->get();

        return $sales->map(function ($sale) {
            $data = [];

            $fieldMap = [
                'sale_date' => $sale->sale_date,
                'beneficiary_name' => $sale->beneficiary_name,
                'serviceType' => optional($sale->serviceType)->label,
                'route' => $sale->route,
                'pnr' => $sale->pnr,
                'reference' => $sale->reference,
                'status' => $sale->status,
                'usd_buy' => $sale->usd_buy,
                'usd_sell' => $sale->usd_sell,
                'provider' => optional($sale->provider)->name,
                'customer' => optional($sale->customer)->name,

            ];

            if ($this->fields) {
                foreach ($this->fields as $field) {
                    if (isset($fieldMap[$field])) {
                        $data[$field] = $fieldMap[$field];
                    }
                }
            } else {
                $data = array_values($fieldMap);
            }

            return $data;
        });
    }

    public function headings(): array
    {
        $defaultHeadings = [
            'التاريخ',
            'المستفيد',
            'الخدمة',
            'Route',
            'PNR',
            'المرجع',
            'action',
            'USD Buy',
            'USD Sell',
            'المزود',
            'الحساب',

        ];

        if (!$this->fields) {
            return $defaultHeadings;
        }

        $headingsMap = [
            'sale_date' => 'التاريخ',
            'beneficiary_name' => 'المستفيد',
            'customer' => 'العميل',
            'serviceType' => 'الخدمة',

            'route' => 'Route',
            'pnr' => 'PNR',
            'reference' => 'المرجع',
            'action' => 'الإجراء',
            'usd_buy' => 'USD Buy',
            'usd_sell' => 'USD Sell',

            'provider' => 'المزود',
            'customer' => 'الحساب',

        ];

        $headings = [];
        foreach ($this->fields as $field) {
            if (isset($headingsMap[$field])) {
                $headings[] = $headingsMap[$field];
            }
        }

        return $headings;
    }
}
