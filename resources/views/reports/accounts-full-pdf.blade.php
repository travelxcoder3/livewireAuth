@php
use Carbon\Carbon;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الحسابات</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            font-size: 12px;
        }

        .header, .footer {
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

        table th, table td {
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
        <h2>تقرير الحسابات</h2>
        <p>{{ $agency->name }} - {{ Carbon::now()->translatedFormat('Y-m-d') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>العميل</th>
                <th>نوع الخدمة</th>
                <th>المزود</th>
                <th>المبلغ (USD)</th>
                <th>المرجع</th>
                <th>PNR</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->created_at?->format('Y-m-d') }}</td>
                    <td>{{ $sale->customer->name ?? '-' }}</td>
                    <td>{{ $sale->service->label ?? '-' }}</td>
                    <td>{{ $sale->provider->name ?? '-' }}</td>
                    <td>{{ number_format($sale->usd_sell, 2) }}</td>
                    <td>{{ $sale->reference }}</td>
                    <td>{{ $sale->pnr }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">لا توجد بيانات</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">
        الإجمالي: {{ number_format($totalSales, 2) }} USD
    </div>

</body>
</html>
