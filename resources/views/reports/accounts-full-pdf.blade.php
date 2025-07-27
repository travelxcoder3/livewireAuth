@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;

    // الحصول على الوكالة مع التحقق من وجود البيانات
    $agency = $sales->first()?->agency;

    // بناء مسار الشعار باستخدام الحقل الصحيح (logo)
    $logoPath = null;
    if ($agency && !empty($agency->logo)) {
        $logoPath = storage_path('app/public/' . ltrim($agency->logo, '/'));
        if (!file_exists($logoPath)) {
            Log::error('Logo file does not exist at path: ' . $logoPath);
            $logoPath = null;
        }
    }

    // تحويل الصورة إلى base64 مع معالجة الأخطاء
    $logoData = null;
    $mime = 'image/png';

    if ($logoPath) {
        try {
            $logoData = base64_encode(file_get_contents($logoPath));
            $mime = mime_content_type($logoPath);
        } catch (Exception $e) {
            Log::error('Failed to process logo image: ' . $e->getMessage());
            $logoData = null;
        }
    }
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
            margin: 20px;
        }

        .header-container {
            position: relative;
            height: 100px;
            margin-bottom: 20px;
        }

        .logo-container {
            position: absolute;
            right: 0;
            top: 0;
        }

        .logo {
            max-height: 100px;
            max-width: 150px;
            object-fit: contain;
        }

        .title-container {
            position: absolute;
            top: 50%;
            right: 50%;
            transform: translate(50%, -50%);
            text-align: center;
            width: 100%;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
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
            text-align: right;
            font-weight: bold;
            margin-top: 20px;
        }

        .total p {
            margin: 0;
            line-height: 1.4;
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

    <!-- رأس التقرير -->
    <div class="header-container">
        <!-- شعار الوكالة -->
        @if ($logoData)
            <div class="logo-container">
                <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار الوكالة" class="logo">
            </div>
        @else
            <div class="logo-container" style="color: #999;">[شعار غير متوفر]</div>
        @endif

        <!-- عنوان التقرير -->
        <div class="title-container">
            <h2 class="title">تقرير الحسابات</h2>
        </div>
    </div>

    <!-- جدول العمليات -->
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

    <!-- المجموع والتاريخ -->
    <div class="total">
        <p>الإجمالي: {{ number_format($totalSales, 2) }} USD</p>
        <p>المستخدم: {{ auth()->user()->name }}</p>
        <p>بتاريخ: {{ Carbon::now()->translatedFormat('j-n-Y') }}</p>
    </div>

    <!-- التذييل -->
    <div class="footer">
        تم إنشاء التقرير في {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}
    </div>

</body>

</html>
