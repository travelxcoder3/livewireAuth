@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $agencyCurrency = Auth::user()?->agency?->currency ?? 'USD';
    
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تقرير المبيعات - {{ now()->format('Y-m-d') }}</title>
    <style>
        @font-face {
            font-family: 'Tahoma';
            src: url('{{ storage_path('fonts/tahoma.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        
        body {
            font-family: 'Tahoma', Arial, sans-serif;
            direction: rtl;
            text-align: right;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid rgb({{ $colors['primary-500'] }});
            padding-bottom: 15px;
        }
        
        .logo {
            height: 70px;
            margin-bottom: 10px;
        }
        
        h1 {
            color: rgb({{ $colors['primary-600'] }});
            font-size: 24px;
            margin: 5px 0;
        }
        
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        th {
            background-color: #f0fdf4;
            color: rgb({{ $colors['primary-600'] }});
            padding: 8px 5px;
            border: 1px solid rgb({{ $colors['primary-100'] }});
            font-weight: bold;
        }
        
        td {
            padding: 6px 5px;
            border: 1px solid #e5e7eb;
        }
        
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-left {
            text-align: left;
        }
        
        .text-green {
            color: rgb({{ $colors['primary-500'] }});
            font-weight: bold;
        }
        
        .text-red {
            color: #ef4444;
            font-weight: bold;
        }
        
        .text-blue {
            color: rgb({{ $colors['primary-600'] }});
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        
        .summary {
            background-color: rgba({{ $colors['primary-100'] }}, 0.2);
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid rgb({{ $colors['primary-100'] }});
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .summary-title {
            font-weight: bold;
            color: rgb({{ $colors['primary-600'] }});
        }
        
        .summary-value {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- يمكنك استبدال هذا بمحتوى صورة الشعار الفعلي -->
        <div class="logo">
            <svg width="200" height="50" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg">
                <rect x="10" y="10" width="180" height="30" rx="5" fill="rgb({{ $colors['primary-500'] }})" opacity="0.2"/>
                <text x="100" y="32" font-family="Tahoma" font-size="16" fill="rgb({{ $colors['primary-600'] }})" text-anchor="middle">
                    {{ $agency->name ?? 'اسم الشركة' }}
                </text>
            </svg>
        </div>
        <h1>تقرير المبيعات</h1>
        <div>فترة التقرير: {{ $startDate ?? 'بداية النشاط' }} إلى {{ $endDate ?? now()->format('Y-m-d') }}</div>
    </div>
    
    <div class="report-info">
        <div>تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</div>
        <div>عدد السجلات: {{ count($sales) }}</div>
    </div>
    
    <div class="summary">
        <div class="summary-item">
            <span class="summary-title">إجمالي المبيعات:</span>
            <span class="summary-value text-green">{{ number_format($sales->sum('amount_received'), 2) }} {{ $agencyCurrency }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-title">إجمالي الأرباح:</span>
            <span class="summary-value text-blue">{{ number_format($sales->sum('sale_profit'), 2) }} {{ $agencyCurrency }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-title">متوسط الربح:</span>
            <span class="summary-value">{{ number_format($sales->avg('sale_profit'), 2) }} {{ $agencyCurrency }}</span>
        </div>
    </div>
    
<table>
        <thead>
            <tr>
                @foreach($fields as $field)
                    @if($field == 'user')<th width="10%">الموظف</th>@endif
                    @if($field == 'sale_date')<th width="8%">التاريخ</th>@endif
                    @if($field == 'beneficiary_name')<th width="12%">المستفيد</th>@endif
                    @if($field == 'customer')<th width="10%">العميل</th>@endif
                    @if($field == 'serviceType')<th width="8%">الخدمة</th>@endif
                    @if($field == 'provider')<th width="8%">المزود</th>@endif
                    @if($field == 'customer_via')<th width="8%">عبر من </th>@endif
                    @if($field == 'usd_buy')<th width="6%" class="text-center">{{ $agencyCurrency }} Buy</th>@endif
                    @if($field == 'usd_sell')<th width="6%" class="text-center">{{ $agencyCurrency }} Sell</th>@endif
                    @if($field == 'sale_profit')<th width="8%" class="text-center">الربح</th>@endif
                    @if($field == 'amount_received')<th width="8%" class="text-center">المبلغ</th>@endif
                    @if($field == 'customer_id')<th width="8%">الحساب</th>@endif
                    @if($field == 'reference')<th width="6%">المرجع</th>@endif
                    @if($field == 'pnr')<th width="6%">PNR</th>@endif
                    @if($field == 'route')<th width="6%">Route</th>@endif
                    @if($field == 'status')<th width="7%">الحالة</th>@endif
                    @if($field == 'payment_method')<th width="7%">حالة الدفع</th>@endif
                    @if($field == 'payment_type')<th width="7%">وسيلة الدفع</th>@endif
                    @if($field == 'receipt_number')<th width="7%">رقم الإيصال</th>@endif
                    @if($field == 'phone_number')<th width="7%">رقم الهاتف</th>@endif
                    @if($field == 'commission')<th width="7%">العمولة</th>@endif
                    @if($field == 'amount_paid')<th width="7%">المدفوع</th>@endif
                    @if($field == 'depositor_name')<th width="7%">اسم المودع</th>@endif
                    @if($field == 'service_date')<th width="7%">تاريخ الخدمة</th>@endif
                    @if($field == 'expected_payment_date')<th width="7%">تاريخ الدفع المتوقع</th>@endif

                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                <tr>
                    @foreach($fields as $field)
                        @if($field == 'user')<td>{{ $sale->user->name ?? '-' }}</td>@endif
                        @if($field == 'sale_date')<td>{{ $sale->sale_date }}</td>@endif
                        @if($field == 'beneficiary_name')<td>{{ $sale->beneficiary_name }}</td>@endif
                        @if($field == 'customer')<td>{{ $sale->customer->name ?? '-' }}</td>@endif
                       @if($field == 'serviceType')
                 <td>{{ $sale->serviceType->label ?? '-' }}</td>

@endif

                        @if($field == 'provider')<td>{{ $sale->provider->name ?? '-' }}</td>@endif
                        @if($field == 'customer_via')
    <td>
        @php
            $viaOptions = [
                   'facebook' => 'فيسبوك',
                                                    'call'     => 'اتصال',
                                                    'instagram'=> 'إنستغرام',
                                                    'whatsapp' => 'واتساب',
                                                    'office'   => 'عبر مكتب',
                                                    'other'    => 'أخرى',
            ];
            $value = strtolower(trim($sale->customer_via ?? ''));
        @endphp
        {{ $viaOptions[$value] ?? $value ?: '-' }}
    </td>
@endif




                        @if($field == 'usd_buy')<td class="text-center text-green">{{ number_format($sale->usd_buy, 2) }}</td>@endif
                        @if($field == 'usd_sell')<td class="text-center text-red">{{ number_format($sale->usd_sell, 2) }}</td>@endif
                        @if($field == 'sale_profit')<td class="text-center text-blue">{{ number_format($sale->sale_profit, 2) }}</td>@endif
                        @if($field == 'amount_received')<td class="text-center">{{ number_format($sale->amount_received, 2) }}</td>@endif
                        @if($field == 'customer_id')<td>{{ $sale->customer->name ?? '-' }}</td>@endif
                        @if($field == 'reference')<td>{{ $sale->reference }}</td>@endif
                        @if($field == 'pnr')<td>{{ $sale->pnr }}</td>@endif
                        @if($field == 'route')<td>{{ $sale->route }}</td>@endif
                       @if($field == 'status')
    <td>
        @php
            $statuses = [
                 'paid'      => 'مدفوع - Paid',
                                                            'unpaid'    => 'غير مدفوع - Unpaid',
                                                            'issued'    => 'تم الإصدار - Issued',
                                                            'reissued'  => 'أعيد إصداره - Reissued',
                                                            'refunded'  => 'تم الاسترداد - Refunded',
                                                            'canceled'  => 'ملغي - Canceled',
                                                            'pending'   => 'قيد الانتظار - Pending',
                                                            'void'      => 'ملغي نهائي - Void',
            ];
        @endphp
        {{ $statuses[$sale->status] ?? '-' }}
    </td>
@endif

                  @if($field == 'payment_method')
    <td>
        @php
            $methods = [
               'kash' => 'كامل',
                                                'part' => 'جزئي',
                                                'all' => 'لم يدفع',
            ];
        @endphp
        {{ $methods[$sale->payment_method] ?? '-' }}
    </td>
@endif


                     @if($field == 'payment_type')
    <td>
        @php
            $types = [
                'cash'            => 'كاش',
                                                    'transfer'        => 'حوالة',
                                                    'account_deposit' => 'إيداع حساب',
                                                    'fund'            => 'صندوق',
                                                    'from_account'    => 'من حساب',
                                                    'wallet'          => 'محفظة',
                                                    'other'           => 'أخرى',
            ];
        @endphp
        {{ $types[$sale->payment_type] ?? '-' }}
    </td>
@endif


                        @if($field == 'receipt_number')<td>{{ $sale->receipt_number }}</td>@endif
                        @if($field == 'phone_number')<td>{{ $sale->phone_number }}</td>@endif
                        @if($field == 'commission')<td class="text-center">{{ number_format($sale->commission, 2) }}</td>@endif
                        @if($field == 'amount_paid')<td class="text-center">{{ number_format($sale->amount_paid, 2) }}</td>@endif
                        @if($field == 'depositor_name')<td>{{ $sale->depositor_name }}</td>@endif
                        @if($field == 'service_date')<td>{{ $sale->service_date }}</td>@endif
                        @if($field == 'expected_payment_date')<td>{{ $sale->expected_payment_date }}</td>@endif

                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    
    
    <div class="footer">
        <div>تم إنشاء هذا التقرير تلقائياً بواسطة نظام إدارة المبيعات</div>
        <div>صفحة <span class="page-number"></span> من <span class="page-count"></span></div>
    </div>
</body>
</html>