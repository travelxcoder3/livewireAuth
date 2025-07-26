@php
    use Carbon\Carbon;
    // جلب مسار الشعار وقراءته
    $logoPath = public_path('images/saas-logo.svg');
    $logoData = file_exists($logoPath)
        ? base64_encode(file_get_contents($logoPath))
        : null;
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
            margin: 20px;
        }
        .header {
            position: relative;
            height: 140px;    /* ارتفاع كافٍ لعنصرين رأس */
            margin-bottom: 20px;
        }
        .logo {
            position: absolute;
            top: 0;
            left: 0;
            width: 180px;
            height: auto;
        }
        .title {
            position: absolute;
            top: 50px;        /* نزلنا العنوان سطرين */
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
            font-size: 30px;
            font-weight: bold;
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
            text-align: right;
            font-weight: bold;
            margin-top: 20px;
        }
        .total p {
            margin: 0;
            line-height: 1.2;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="header">
        @if($logoData)
            <img src="data:image/svg+xml;base64,{{ $logoData }}" alt="شعار الشركة" class="logo">
        @endif
        <h2 class="title">تقرير المبيعات</h2>
    </div>

    <table>
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
                <th>حالة الدفع</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->created_at->format('Y-m-d') }}</td>
                    <td>{{ optional($sale->user)->name ?? '-' }}</td>
                    <td>{{ optional($sale->service)->label ?? '-' }}</td>
                    <td>{{ optional($sale->provider)->name ?? '-' }}</td>
                    <td>{{ optional($sale->customer)->name ?? '-' }}</td>
                    <td style="text-align: right;">{{ number_format($sale->usd_sell, 2) }}</td>
                    <td>{{ $sale->reference }}</td>
                    <td>{{ $sale->pnr }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $sale->customer_via)) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">لا توجد بيانات</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">
        <p>الإجمالي: {{ number_format($totalSales, 2) }} USD</p>
        <p>المستخدم: {{ auth()->user()->name }}</p>
        <p>بتاريخ: {{ Carbon::now()->translatedFormat('j-n-Y') }}</p>
    </div>

    <div class="footer">
        تم إنشاء التقرير في {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}
    </div>

</body>
</html>
