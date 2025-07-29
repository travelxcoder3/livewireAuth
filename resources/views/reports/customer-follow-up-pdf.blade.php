@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use Carbon\Carbon;

    // شعار الوكالة
    $agency = Auth::user()->agency;
    $logoPath = null;
    if ($agency && !empty($agency->logo)) {
        $logoPath = storage_path('app/public/' . ltrim($agency->logo, '/'));
        if (!file_exists($logoPath)) {
            Log::error('Logo file does not exist at path: ' . $logoPath);
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
            Log::error('Failed to process logo image: ' . $e->getMessage());
            $logoData = null;
        }
    }
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تقرير تتبع العملاء</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            max-height: 80px;
            max-width: 150px;
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
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #aaa;
            padding: 6px;
            text-align: center;
        }

        th {
            background-color: #eee;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div style="flex:1;">
            @if ($logoData)
                <img src="data:{{ $mime }};base64,{{ $logoData }}" class="logo" />
            @endif
        </div>
        <div class="title">تقرير تتبع العملاء</div>
        <div style="flex:1;"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>اسم المستفيد</th>
                <th>رقم هاتف المستفيد</th>
                <th>نوع الخدمة</th>
                <th>المسار/التفاصيل</th>
                <th>تاريخ الخدمة</th>
                <th>حساب العميل</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ $sale->beneficiary_name }}</td>
                    <td>{{ $sale->phone_number }}</td>
                    <td>{{ $sale->service->label ?? '-' }}</td>
                    <td>{{ $sale->route ?? '-' }}</td>
                    <td>{{ $sale->service_date ? \Carbon\Carbon::parse($sale->service_date)->format('Y-m-d') : '-' }}</td>
                    <td>{{ $sale->customer->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">لا توجد بيانات</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>إجمالي السجلات: {{ $totalCount }}</p>
        <p>تاريخ الإنشاء: {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}</p>
    </div>
</body>

</html>
