@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\NumberToWords;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $agencyCurrency = Auth::user()?->agency?->currency ?? 'USD';

    use Carbon\Carbon;

    $logoPath = storage_path('app/public/' . $sale->agency->logo);
    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    $mime = file_exists($logoPath) ? mime_content_type($logoPath) : null;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة العملية</title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            font-size: 13px;
            color: #000;
            padding: 30px;
        }

        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: rgb({{ $colors['primary-600'] }});
        }

        .top-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .top-info div {
            line-height: 1.5;
        }

        .details {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .details div {
            line-height: 1.6;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .table th, .table td {
            border: 1px solid #aaa;
            padding: 8px;
            text-align: center;
        }

        .summary {
            font-size: 12px;
            line-height: 1.6;
            margin-top: 10px;
        }

        .summary div {
            margin-bottom: 4px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            font-size: 13px;
        }

        .signatures div {
            text-align: center;
            width: 40%;
        }

        .signatures .line {
            margin-top: 30px;
            border-top: 1px solid #ccc;
        }

        .logo {
    position: absolute;
    top: 0;
    left: 0;
    width: 120px; /* العرض */
    height: 60px; /* الارتفاع */
    object-fit: contain; /* يحافظ على أبعاد الصورة */
}


    </style>
</head>
<body>




{{-- العنوان أولاً --}}
<div class="title">فاتورة العملية</div>

{{-- ثم الشعار تحته --}}
@if($logoData && $mime)
    <div style="text-align: center; margin-bottom: 20px;">
        <img
            src="data:{{ $mime }};base64,{{ $logoData }}"
            alt="شعار الوكالة"
            style="width: 140px; height: 70px; object-fit: contain;"
        >
    </div>
@endif


    {{-- معلومات الفاتورة --}}
    <div class="top-info">
        <div>
            <strong>Order No:</strong> {{ $sale->id }} / {{ $sale->agency_id }}<br>
            <strong>Date:</strong> {{ $sale->sale_date }}<br>
            <strong>Invoice No:</strong> INV-{{ str_pad($sale->id, 5, '0', STR_PAD_LEFT) }}
        </div>
        <div style="text-align: left;">
            <strong>{{ strtoupper($sale->agency->name ?? 'AGENCY') }}</strong><br>
            {{ $sale->agency->address ?? 'العنوان غير متوفر' }}<br>
            {{ $sale->agency->phone ?? '' }}
        </div>
    </div>

    {{-- بيانات العميل --}}
    <div class="details">
        <div>
            الاسم: {{ $sale->beneficiary_name }}<br>
            الهاتف: {{ $sale->phone_number }}
        </div>
        <div>
            PNR: {{ $sale->pnr }}<br>
            المرجع: {{ $sale->reference }}
        </div>
    </div>

    {{-- جدول البيانات --}}
   <table class="table">
    <thead>
        <tr>
            <th>اسم الموظف</th>
            <th>اسم العميل</th>
            <th>اسم المستفيد</th>
            <th>الهاتف</th>
            <th>الخدمة</th>
            <th>المزود</th>
            <th>المسار</th>
            <th>PNR</th>
            <th>المرجع</th>
            <th>سعر البيع</th>
            <th>سعر الشراء</th>
            <th>المدفوع</th>
            <th>المتبقي</th>
            <th>الحدث</th>
            <th>الوكالة</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $sale->user->name ?? '-' }}</td>
            <td>{{ $sale->customer->name ?? '-' }}</td>
            <td>{{ $sale->beneficiary_name }}</td>
            <td>{{ $sale->phone_number }}</td>
            <td>{{ $sale->service?->label ?? '-' }}</td>
            <td>{{ $sale->provider?->name ?? '-' }}</td>
            <td>{{ $sale->route ?? '-' }}</td>
            <td>{{ $sale->pnr ?? '-' }}</td>
            <td>{{ $sale->reference ?? '-' }}</td>
            <td>{{ number_format($sale->usd_sell, 2) }}</td>
            <td>{{ number_format($sale->usd_buy, 2) }}</td>
            <td>{{ number_format($sale->paid_total, 2) }}</td>
            <td>{{ number_format($sale->remaining, 2) }}</td>
            <td>{{ strtoupper($sale->status) }}</td>
            <td>{{ $sale->agency->name ?? '-' }}</td>
        </tr>
    </tbody>
</table>


    {{-- الملخص --}}
    <div class="summary">
        <div><strong>Currency:</strong> {{ $agencyCurrency }}</div>
        <div><strong>TAX:</strong> 0.00</div>
        <div><strong>Net:</strong> {{ number_format($sale->usd_sell, 2) }}</div>
        <div><strong>Gross:</strong> {{ number_format($sale->usd_sell, 2) }}</div>
        <div><strong>Only:</strong> {{ ucwords(NumberToWords::convert($sale->usd_sell)) }} {{ $agencyCurrency }}</div>
    </div>

    {{-- التوقيع --}}
    <div class="signatures">
        <div>
            Approved by
            <div class="line"></div>
        </div>
        <div>
            Prepared by
            <div class="line"></div>
        </div>
    </div>

</body>
</html>
