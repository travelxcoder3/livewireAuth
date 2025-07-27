@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\NumberToWords;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $agencyCurrency = Auth::user()?->agency?->currency ?? 'USD';

     use Carbon\Carbon;
$saleExample = $invoice->sales->first(); // أول عملية
$logoPath = $saleExample && $saleExample->agency && $saleExample->agency->logo
    ? storage_path('app/public/' . $saleExample->agency->logo)
    : null;
$logoData = $logoPath && file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
$mime = $logoPath && file_exists($logoPath) ? mime_content_type($logoPath) : null;

@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة مجمعة</title>
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

        .details {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 20px;
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

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            font-size: 13px;
        }

        .signatures .line {
            margin-top: 30px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>
<body>

    <div class="title">فاتورة مجمعة</div>
    @if($logoData && $mime)
    <div style="text-align: center; margin-bottom: 20px;">
        <img
            src="data:{{ $mime }};base64,{{ $logoData }}"
            alt="شعار الوكالة"
            style="width: 140px; height: 70px; object-fit: contain;"
        >
    </div>
@endif

    <div class="top-info">
        <div>
            <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
            <strong>Date:</strong> {{ $invoice->date }}<br>
            <strong>Client:</strong> {{ $invoice->entity_name }}
        </div>
        <div style="text-align: left;">
            <strong>{{ strtoupper($invoice->agency->name ?? 'AGENCY') }}</strong><br>
            {{ $invoice->agency->address ?? 'العنوان غير متوفر' }}<br>
            {{ $invoice->agency->phone ?? '' }}
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>اسم المستفيد</th>
                <th>الخدمة</th>
                <th>PNR</th>
                <th>المرجع</th>
                <th>المبلغ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->sales as $sale)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $sale->beneficiary_name }}</td>
                <td>{{ $sale->service->label ?? '-' }}</td>
                <td>{{ $sale->pnr ?? '-' }}</td>
                <td>{{ $sale->reference ?? '-' }}</td>
                <td>{{ number_format($sale->usd_sell, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" style="text-align: left;">الإجمالي</th>
                <th>{{ number_format($invoice->sales->sum('usd_sell'), 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <div><strong>Currency:</strong> {{ $agencyCurrency }}</div>
        <div><strong>TAX:</strong> 0.00</div>
        <div><strong>Net:</strong> {{ number_format($invoice->sales->sum('usd_sell'), 2) }}</div>
        <div><strong>Gross:</strong> {{ number_format($invoice->sales->sum('usd_sell'), 2) }}</div>
        <div><strong>Only:</strong> {{ ucwords(NumberToWords::convert($invoice->sales->sum('usd_sell'))) }} {{ $agencyCurrency }}</div>
    </div>

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