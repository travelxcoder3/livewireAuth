@php
    use Carbon\Carbon;
    // جلب مسار الشعار وقراءة محتواه
    $logoPath = public_path('images/saas-logo.svg');
    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تفاصيل الحساب</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            font-size: 13px;
            margin: 20px;
        }

        .header {
            position: relative;
            height: 120px;
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
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        @if ($logoData)
            <img src="data:image/svg+xml;base64,{{ $logoData }}" alt="شعار الشركة" class="logo">
        @endif
        <h2 class="title">تفاصيل الحساب</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>اسم المستفيد</th>
                <th>العميل</th>
                <th>نوع الخدمة</th>
                <th>المزود</th>
                <th>PNR</th>
                <th>المرجع</th>
                <th>سعر البيع</th>
                <th>سعر الشراء</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $sale->sale_date ?? '-' }}</td>
                <td>{{ $sale->beneficiary_name ?? '-' }}</td>
                <td>{{ $sale->customer->name ?? '-' }}</td>
                <td>{{ $sale->service->label ?? '-' }}</td>
                <td>{{ $sale->provider->name ?? '-' }}</td>
                <td>{{ $sale->pnr ?? '-' }}</td>
                <td>{{ $sale->reference ?? '-' }}</td>
                <td>{{ number_format($sale->usd_sell ?? 0, 2) }} USD</td>
                <td>{{ number_format($sale->usd_buy ?? 0, 2) }} USD</td>
            </tr>
        </tbody>
    </table>
    {{-- توقيع/ختم أو توقيت الإنشاء في الأسفل --}}
    <div class="footer">
        تم إنشاء التقرير في {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}
    </div>
</body>

</html>
