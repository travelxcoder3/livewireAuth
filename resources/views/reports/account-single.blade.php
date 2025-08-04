@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;

    $logoPath = storage_path('app/public/' . ($sale->agency->logo ?? ''));
    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    $mime = file_exists($logoPath) ? mime_content_type($logoPath) : 'image/png';
      $currency = $sale->agency->currency ?? 'USD';
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
            margin: 30px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            width: 140px;
            height: 80px;
            object-fit: contain;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            flex-grow: 1;
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

    <!-- رأس الصفحة: شعار + عنوان -->
    <div class="header">
        <div style="flex: 1;">
            @if ($logoData)
                <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار الوكالة" class="logo">
            @endif
        </div>
        <div class="title">تفاصيل الحساب</div>
        <div style="flex: 1;"></div>
    </div>

    <!-- جدول البيانات -->
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
                <td>{{ number_format($sale->usd_sell ?? 0, 2) }} {{ $currency }}</td>
                <td>{{ number_format($sale->usd_buy ?? 0, 2) }} {{ $currency }}</td>

            </tr>
        </tbody>
    </table>

    <!-- تذييل -->
    <div class="footer">
        تم إنشاء التقرير في {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}
    </div>

</body>

</html>
