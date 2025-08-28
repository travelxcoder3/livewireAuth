<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AccountsExport implements FromCollection, WithHeadings
{
    protected $fields;
    protected $filters;

    public function __construct($fields = null, $filters = [])
    {
        $this->fields  = $fields;
        $this->filters = $filters;
    }

    public function collection()
    {
        // حصر النتائج على الوكالة الحالية وفروعها
        $agency = Auth::user()->agency;
        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $query = Sale::with(['customer','serviceType','provider','intermediary','account','user'])
            ->whereIn('agency_id', $agencyIds);

        // الفلاتر العامة
        if (!empty($this->filters)) {
            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('sale_date', [$this->filters['start_date'], $this->filters['end_date']]);
            } elseif (!empty($this->filters['start_date'])) {
                $query->whereDate('sale_date', '>=', $this->filters['start_date']);
            } elseif (!empty($this->filters['end_date'])) {
                $query->whereDate('sale_date', '<=', $this->filters['end_date']);
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
                $query->where('pnr', 'like', '%'.$this->filters['pnr'].'%');
            }

            if (!empty($this->filters['reference'])) {
                $query->where('reference', 'like', '%'.$this->filters['reference'].'%');
            }

            // فلترة الموظف: id أو اسم
            if (!empty($this->filters['employee'])) {
                $emp = trim($this->filters['employee']);
                if (is_numeric($emp)) {
                    $query->where('user_id', (int) $emp);
                } else {
                    $like = '%'.$emp.'%';
                    $query->whereHas('user', fn($u) => $u->where('name', 'like', $like));
                }
            }

            // دعم قديم إن وُجد user_id صريح
            if (!empty($this->filters['user_id'])) {
                $query->where('user_id', $this->filters['user_id']);
            }
        }

        $sales = $query->latest()->get();

        return $sales->map(function ($sale) {
            $fieldMap = [
                'sale_date'        => $sale->sale_date,
                'beneficiary_name' => $sale->beneficiary_name,
                'serviceType'      => optional($sale->serviceType)->label,
                'route'            => $sale->route,
                'pnr'              => $sale->pnr,
                'reference'        => $sale->reference,
                'status'           => $sale->status,
                'usd_buy'          => $sale->usd_buy,
                'usd_sell'         => $sale->usd_sell,
                'provider'         => optional($sale->provider)->name,
                'customer'         => optional($sale->customer)->name,
                'account'          => optional($sale->account)->name,
            ];

            if ($this->fields) {
                $data = [];
                foreach ($this->fields as $field) {
                    if (array_key_exists($field, $fieldMap)) {
                        $data[$field] = $fieldMap[$field];
                    }
                }
                return $data;
            }

            // إن لم تُحدد الحقول نعيد الكل بالترتيب أعلاه
            return array_values($fieldMap);
        });
    }

    public function headings(): array
    {
        // العناوين الافتراضية موافقة للترتيب في fieldMap
        $defaultHeadings = [
            'التاريخ',
            'المستفيد',
            'الخدمة',
            'Route',
            'PNR',
            'المرجع',
            'الحالة',
            'USD Buy',
            'USD Sell',
            'المزوّد',
            'العميل',
        ];

        if (!$this->fields) {
            return $defaultHeadings;
        }

        $headingsMap = [
            'sale_date'        => 'التاريخ',
            'beneficiary_name' => 'المستفيد',
            'serviceType'      => 'الخدمة',
            'route'            => 'Route',
            'pnr'              => 'PNR',
            'reference'        => 'المرجع',
            'status'           => 'الحالة',
            'usd_buy'          => 'USD Buy',
            'usd_sell'         => 'USD Sell',
            'provider'         => 'المزوّد',
            'customer'         => 'العميل',
            'account'          => 'الحساب',
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
