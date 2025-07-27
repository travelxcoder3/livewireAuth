@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Log;

    // الحصول على الوكالة من أول عملية بيع
    $agency = $sales->first()?->agency;

    // بناء مسار الشعار إذا توفر
    $logoPath = null;
    if ($agency && !empty($agency->logo)) {
        $logoPath = storage_path('app/public/' . ltrim($agency->logo, '/'));
        if (!file_exists($logoPath)) {
            Log::error('Logo file not found: ' . $logoPath);
            $logoPath = null;
        }
    }

    // تحويل الشعار إلى base64
    $logoData = null;
    $mime = 'image/png';
    if ($logoPath) {
        try {
            $logoData = base64_encode(file_get_contents($logoPath));
            $mime = mime_content_type($logoPath);
        } catch (Exception $e) {
            Log::error('Error reading logo: ' . $e->getMessage());
        }
    }
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
            height: 120px;
            margin-bottom: 30px;
        }

        .logo {
            position: absolute;
            top: 0;
            right: 0;
            max-width: 150px;
            max-height: 100px;
            object-fit: contain;
        }

        .title {
            position: absolute;
            top: 50%;
            right: 50%;
            transform: translate(50%, -50%);
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            text-align: center;
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
            <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار الوكالة" class="logo">
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
                <th>المبلغ (USD)</th>
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
