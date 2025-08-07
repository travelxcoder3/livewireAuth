@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Log;

    // الحصول على الوكالة المرتبطة بالعملية
    $agency = $sale->agency ?? null;

    // محاولة تحميل الشعار من التخزين
    $logoPath = null;
    if ($agency && $agency->logo) {
        $logoPath = storage_path('app/public/' . ltrim($agency->logo, '/'));
        if (!file_exists($logoPath)) {
            Log::error('Logo not found: ' . $logoPath);
            $logoPath = null;
        }
    }

    $logoData = null;
    $mime = 'image/png';
    if ($logoPath) {
        try {
            $logoData = base64_encode(file_get_contents($logoPath));
            $mime = mime_content_type($logoPath);
        } catch (Exception $e) {
            Log::error('Logo read error: ' . $e->getMessage());
        }
    }
      $currency = $sale->agency->currency ?? 'USD';
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل عملية البيع</title>
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
            right: 0;
            max-width: 180px;
            max-height: 100px;
            object-fit: contain;
        }

        .title {
            position: absolute;
            top: 50%;
            right: 50%;
            transform: translate(50%, -50%);
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th, table td {
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
            <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار الوكالة" class="logo">
        @endif
        <h2 class="title">تفاصيل عملية البيع</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>الموظف</th>
                <th>المستفيد</th>
                <th>الخدمة</th>
                <th>المزود</th>
                <th>PNR</th>
                <th>المرجع</th>
                <th>سعر البيع</th>
                <th>سعر الشراء</th>
                <th>العميل</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $sale->sale_date }}</td>
                <td>{{ $sale->user->name ?? '-' }}</td>
                <td>{{ $sale->beneficiary_name }}</td>
                <td>{{ $sale->service->label ?? '-' }}</td>
                <td>{{ $sale->provider->name ?? '-' }}</td>
                <td>{{ $sale->pnr }}</td>
                <td>{{ $sale->reference }}</td>
                <td>{{ number_format($sale->usd_sell, 2) }} {{ $currency }}</td>
                <td>{{ number_format($sale->usd_buy, 2) }} {{ $currency }}</td>
                <td>{{ $sale->customer->name ?? '-' }}</td>
                <td>{{ $sale->status }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        تم إنشاء التقرير في {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}
    </div>

</body>
</html>
