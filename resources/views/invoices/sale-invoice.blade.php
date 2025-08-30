@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\NumberToWords;
    use Carbon\Carbon;

    /** الثيم والعملة **/
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $agencyCurrency = $agencyCurrency ?? (Auth::user()?->agency?->currency ?? 'USD');

    /** شعار الوكالة **/
    $stored   = $sale->agency->logo ?? null;
    $logoPath = $stored ? storage_path('app/public/' . $stored) : null;
    $logoData = ($logoPath && file_exists($logoPath)) ? base64_encode(file_get_contents($logoPath)) : null;
    $mime     = ($logoPath && file_exists($logoPath)) ? mime_content_type($logoPath) : null;

    /** تمرير الفاتورة إن وُجدت من Livewire (ربط الضريبة برقم الفاتورة) **/
    $invoice = $invoice ?? null;

    /** مبالغ مدفوعة ومتبقي (تأمين) **/
    $paid_total = isset($sale->paid_total)
        ? (float) $sale->paid_total
        : ((float)($sale->amount_paid ?? 0) + (float)($sale->collections->sum('amount') ?? 0));

    $remaining  = isset($sale->remaining)
        ? (float) $sale->remaining
        : ((float)($sale->usd_sell ?? 0) - $paid_total);

    /** أساس الفاتورة والضريبة والصافي **/
    $base = isset($base) ? (float)$base : (float)($sale->usd_sell ?? 0);
    if (in_array($sale->status ?? '', ['Refund-Full','Refund-Partial'])) {
        $base = -abs($base);
    }

   $tax = (float)($tax ?? 0.0);
$net = (float)($net ?? ($base + $tax));


    $hasTax   = abs($tax) > 0.00001;
    $grossVal = $hasTax ? $net : $base;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة العملية</title>
    <style>
        body{font-family:'Tajawal',sans-serif;direction:rtl;font-size:13px;color:#000;padding:30px}
        .title{text-align:center;font-size:20px;font-weight:bold;margin-bottom:20px;color:rgb({{ $colors['primary-600'] }})}
        .top-info{display:flex;justify-content:space-between;margin-bottom:10px;font-size:12px}
        .top-info div{line-height:1.5}
        .details{display:flex;justify-content:space-between;font-size:13px;margin-bottom:20px}
        .details div{line-height:1.6}
        .table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:15px}
        .table th,.table td{border:1px solid #aaa;padding:8px;text-align:center}
        .summary{font-size:12px;line-height:1.6;margin-top:10px}
        .summary div{margin-bottom:4px}
        .signatures{display:flex;justify-content:space-between;margin-top:40px;font-size:13px}
        .signatures div{text-align:center;width:40%}
        .signatures .line{margin-top:30px;border-top:1px solid #ccc}
        .logo{position:absolute;top:0;left:0;width:120px;height:60px;object-fit:contain}
    </style>
</head>
<body>

<div class="title">فاتورة العملية</div>

@if($logoData && $mime)
    <div style="text-align:center;margin-bottom:20px;">
        <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار الوكالة" style="width:140px;height:70px;object-fit:contain;">
    </div>
@endif

<div class="top-info">
    <div>
        <strong>Order No:</strong> {{ $sale->id }} / {{ $sale->agency_id }}<br>
        <strong>Date:</strong> {{ $invoice->date ?? $sale->sale_date }}<br>
        <strong>Invoice No:</strong> {{ $invoice->invoice_number ?? ('INV-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT)) }}
    </div>
    <div style="text-align:left;">
        <strong>{{ strtoupper($sale->agency->name ?? 'AGENCY') }}</strong><br>
        {{ $sale->agency->address ?? 'العنوان غير متوفر' }}<br>
        {{ $sale->agency->phone ?? '' }}<br>
        {{ $sale->agency->email ?? '' }}<br>
        {{ $sale->agency->tax_number ? 'VAT: '.$sale->agency->tax_number : '' }}
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
        </tr>
    </thead>
    <tbody>
        <tr>
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
            <td>{{ number_format($paid_total, 2) }}</td>
            <td>{{ number_format($remaining, 2) }}</td>
            <td>{{ strtoupper($sale->status) }}</td>
        </tr>
    </tbody>
</table>

<div class="summary">
    <div><strong>Paid:</strong> {{ number_format($paid_total, 2) }}</div>
    <div><strong>Remaining:</strong> {{ number_format($remaining, 2) }}</div>
</div>

<div class="summary">
    <div><strong>Currency:</strong> {{ $agencyCurrency }}</div>
    @if($hasTax)
        <div><strong>TAX:</strong> {{ number_format($tax, 2) }}</div>
    @endif
    <div><strong>Net:</strong> {{ number_format($base, 2) }}</div>
    <div><strong>Gross:</strong> {{ number_format($grossVal, 2) }}</div>
    <div><strong>Only:</strong> {{ ucwords(NumberToWords::convert($grossVal)) }} {{ $agencyCurrency }}</div>
</div>

<div class="signatures">
    <div>
        Approved by / توقيع العميل
        <div class="line"></div>
    </div>
    <div>
        Prepared by: {{ $sale->user->name ?? (Auth::user()->name ?? 'Staff') }}
        <div class="line"></div>
    </div>
</div>

<div style="margin-top:12px;font-size:11px;color:#555;display:flex;justify-content:space-between;">
    <div>
        Printed by: {{ $sale->user->name ?? (Auth::user()->name ?? 'Staff') }}
        &nbsp;|&nbsp;
        Role: {{ optional($sale->user)->getRoleNames()->first() ?? '-' }}
    </div>
    <div>
        Printed at: {{ Carbon::now()->format('Y-m-d H:i') }}
    </div>
</div>

</body>
</html>
