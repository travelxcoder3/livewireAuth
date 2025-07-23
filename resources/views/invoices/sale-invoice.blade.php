
@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\NumberToWords;

    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $agencyCurrency = Auth::user()?->agency?->currency ?? 'USD';
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
    </style>
</head>
<body>

    <div class="title">فاتورة العملية</div>

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

    <table class="table">
        <thead>
            <tr>
                <th>Amount</th>
                <th>Class</th>
                <th>Sector</th>
                <th>Passenger Name</th>
                <th>Doc No</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($sale->usd_sell, 2) }}</td>
                <td>{{ $sale->class ?? '-' }}</td>
                <td>{{ $sale->sector ?? '-' }}</td>
                <td>{{ $sale->beneficiary_name }}</td>
                <td>{{ $sale->doc_no ?? '-' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <div><strong>Currency:</strong> {{ $agencyCurrency }}</div>
        <div><strong>TAX:</strong> 0.00</div>
        <div><strong>Net:</strong> {{ number_format($sale->usd_sell, 2) }}</div>
        <div><strong>Gross:</strong> {{ number_format($sale->usd_sell, 2) }}</div>
        <div><strong>Only:</strong> {{ ucwords(NumberToWords::convert($sale->usd_sell)) }} {{ $agencyCurrency }}</div>
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
