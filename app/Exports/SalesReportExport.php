<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $sales;
    protected array $fields;
    protected array $headers;
    protected array $formats;
    protected string $currency;

    public function __construct(array $data)
    {
        $this->sales    = collect($data['sales'] ?? []);
        $this->fields   = $data['fields']  ?? [];
        $this->headers  = $data['headers'] ?? [];
        $this->formats  = $data['formats'] ?? [];
        $this->currency = $data['currency'] ?? 'USD';

        // fallback لمنع ورقة فارغة إن وصلت fields فاضية
        if (empty($this->fields)) {
            $this->fields = [
                'sale_date','beneficiary_name','customer.name','service.label','provider.name',
                'service_date','customer_via','usd_buy','usd_sell','sale_profit','amount_paid',
                'total_paid','remaining_payment','expected_payment_date','reference','pnr','route',
                'status','agency.name','payment_method','payment_type','receipt_number','phone_number',
                'depositor_name','commission','user.name',
            ];
        }
    }

    public function collection(): Collection
    {
        return $this->sales;
    }

    public function headings(): array
    {
        return array_map(fn($f)=>$this->headers[$f] ?? $f, $this->fields);
    }

public function map($sale): array
{
    $nf = fn($v)=>number_format((float)$v, 2, '.', ',');
    $dash = '-';  // نفس واجهة الجدول

    // محولات الواجهة
    $pmethod = [
        'kash'=>'كامل','part'=>'جزئي','all'=>'لم يدفع','paid'=>'مدفوع','unpaid'=>'غير مدفوع'
    ];
    $ptype = [
        'cash'=>'كاش','transfer'=>'حوالة','account_deposit'=>'إيداع حساب',
        'fund'=>'صندوق','from_account'=>'من حساب','wallet'=>'محفظة','other'=>'أخرى'
    ];
    $via = [
        'facebook'=>'فيسبوك','call'=>'اتصال','instagram'=>'إنستغرام',
        'whatsapp'=>'واتساب','office'=>'عبر مكتب','other'=>'أخرى'
    ];
    $statusMap = [
        'paid'=>'مدفوع','unpaid'=>'غير مدفوع','issued'=>'تم الإصدار','reissued'=>'أعيد إصداره',
        'refunded'=>'تم الاسترداد','canceled'=>'ملغي','pending'=>'قيد الانتظار',
        'void'=>'ملغي نهائي','Refund-Full'=>'Refund-Full','Refund-Partial'=>'Refund-Partial','Issued'=>'Issued'
    ];

    $row = [];
    foreach ($this->fields as $f) {
        $val = data_get($sale, $f);
        $fmt = $this->formats[$f] ?? null;

        switch ($fmt) {
            case 'money':
                $row[] = $val === null ? $dash : ($nf($val).' '.$this->currency);
                break;
            case 'date':
                $row[] = $val ? (string)\Carbon\Carbon::parse($val)->format('Y-m-d H:i') : $dash;
                break;
            case 'status':
                $row[] = $statusMap[$val] ?? ($val ?? $dash);
                break;
            case 'payment_method':
                $row[] = $pmethod[$val] ?? ($val ?? $dash);
                break;
            case 'payment_type':
                $row[] = $ptype[$val] ?? ($val ?? $dash);
                break;
            case 'customer_via':
                $row[] = $via[$val] ?? ($val ?? $dash);
                break;
            default:
                // اجبار نص لمنع الصيغة العلمية والفراغ
                $row[] = ($val === null || $val === '') ? $dash : (string)$val;
        }
    }

    return $row;
}

}
