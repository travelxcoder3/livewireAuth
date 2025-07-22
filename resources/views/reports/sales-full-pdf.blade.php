@php
    use Carbon\Carbon;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تقرير المبيعات</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            font-size: 12px;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 20px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        table th,
        table td {
            border: 1px solid #aaa;
            padding: 6px;
            text-align: center;
        }

        table th {
            background-color: #eee;
        }

        .total {
            text-align: left;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>تقرير المبيعات</h2>
        <p>{{ $agency->name }} - {{ Carbon::now()->translatedFormat('Y-m-d') }}</p>
        <p>
            @if ($startDate)
                من تاريخ: {{ Carbon::parse($startDate)->format('Y-m-d') }}
            @endif
            @if ($endDate)
                إلى تاريخ: {{ Carbon::parse($endDate)->format('Y-m-d') }}
            @endif
        </p>
    </div>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>الموظف المسؤول</th>
                <th>نوع الخدمة</th>
                <th>المزود</th>
                <th>حساب العميل</th>
                <th>(USD) المبلغ</th>
                <th>المرجع</th>
                <th>PNR</th>
                <th>العميل عبر</th>
                <th>طريقة الدفع</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sales as $sale)
                <tr>
                    <td>{{ $sale->created_at->format('Y-m-d') }}</td>
                    <td>{{ optional($sale->user)->name ?? '-' }}</td>

                    {{-- عرض اسم الخدمة عبر العلاقة service --}}
                    <td>{{ optional($sale->service)->label ?? '-' }}</td>

                    <td>{{ optional($sale->provider)->name ?? '-' }}</td>
                    <td>{{ optional($sale->customer)->name ?? '-' }}</td>
                    <td style="text-align: right;">{{ number_format($sale->usd_sell, 2) }}</td>
                    <td>{{ $sale->reference }}</td>
                    <td>{{ $sale->pnr }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $sale->customer_via)) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>


    <div class="total">
        الإجمالي: {{ number_format($totalSales, 2) }} USD
    </div>

    <div class="footer">
        تم إنشاء التقرير في: {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}
    </div>

</body>

</html>
