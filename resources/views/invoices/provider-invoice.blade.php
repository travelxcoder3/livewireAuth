@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\NumberToWords;

    /** المدخلات المتوقعة:
     * $provider  : App\Models\Provider
     * $group     : كائن لمجموعة واحدة من ProviderInvoiceOverview (group_key, beneficiary_name, service_label, cost_total_true, refund_total, net_cost, sale_date, scenarios[])
     * $invoice   : (اختياري) App\Models\Invoice عند الحفظ قبل التوليد
     * $currency  : (اختياري) رمز العملة
     */

    $agency     = Auth::user()?->agency ?? optional($provider)->agency;
    $themeName  = strtolower($agency->theme_color ?? 'emerald');
    $colors     = ThemeService::getCurrentThemeColors($themeName);
    $currency   = $currency ?? ($agency->currency ?? 'USD');

    // شعار
    $stored   = $agency->logo ?? null;
    $logoPath = $stored ? storage_path('app/public/' . $stored) : null;
    $logoData = ($logoPath && file_exists($logoPath)) ? base64_encode(file_get_contents($logoPath)) : null;
    $mime     = ($logoPath && file_exists($logoPath)) ? mime_content_type($logoPath) : null;

    // أرقام الفاتورة
    $invNo  = $invoice->invoice_number ?? ('PRV-' . ($group->group_key ?? uniqid()));
    $invDt  = $invoice->date ?? ($group->sale_date ?? now()->toDateString());

    // الإجماليات: إن وُجدت بالقيد استخدمها، وإلا من المجموعة + ضريبة مدخلة
    $base = isset($invoice) ? (float)($invoice->subtotal ?? $group->net_cost ?? 0) : (float)($group->net_cost ?? 0);
    $tax  = isset($invoice) ? (float)($invoice->tax_total ?? 0) : (float)($tax ?? 0);
    $net  = isset($invoice) ? (float)($invoice->grand_total ?? ($base + $tax)) : ($base + $tax);

    $hasTax = abs($tax) > 0.00001;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة مزوّد</title>
    <style>
        body{font-family:'Tajawal',sans-serif;direction:rtl;font-size:13px;color:#000;padding:30px}
        .title{text-align:center;font-size:20px;font-weight:bold;margin-bottom:20px;color:rgb({{ $colors['primary-600'] }})}
        .top{display:flex;justify-content:space-between;margin-bottom:10px;font-size:12px}
        .top div{line-height:1.5}
        .details{display:flex;justify-content:space-between;font-size:13px;margin:15px 0}
        .details div{line-height:1.8}
        table{width:100%;border-collapse:collapse;font-size:12px;margin-top:10px}
        th,td{border:1px solid #aaa;padding:8px;text-align:center}
        .summary{font-size:12px;line-height:1.6;margin-top:12px}
        .summary div{margin-bottom:4px}
        .sign{display:flex;justify-content:space-between;margin-top:40px;font-size:13px}
        .line{margin-top:30px;border-top:1px solid #ccc}
    </style>
</head>
<body>

<div class="title">فاتورة مزوّد</div>

@if($logoData && $mime)
    <div style="text-align:center;margin-bottom:20px;">
        <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار" style="width:140px;height:70px;object-fit:contain;">
    </div>
@endif

<div class="top">
    <div>
        <strong>Invoice No:</strong> {{ $invNo }}<br>
        <strong>Date:</strong> {{ $invDt }}<br>
        <strong>Provider:</strong> {{ $provider->name ?? '—' }}
    </div>
    <div style="text-align:left;">
        <strong>{{ strtoupper($agency->name ?? 'AGENCY') }}</strong><br>
        {{ $agency->address ?? 'العنوان غير متوفر' }}<br>
        {{ $agency->phone ?? '' }}<br>
        {{ $agency->email ?? '' }}<br>
        {{ !empty($agency->tax_number) ? 'VAT: '.$agency->tax_number : '' }}
    </div>
</div>

<div class="details">
    <div>
        المستفيد: {{ $group->beneficiary_name ?? '—' }}<br>
        الخدمة: {{ $group->service_label ?? '—' }}
    </div>
    <div>
        أصل التكلفة: {{ number_format((float)($group->cost_total_true ?? 0),2) }} {{ $currency }}<br>
        الاستردادات: {{ number_format((float)($group->refund_total ?? 0),2) }} {{ $currency }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>التاريخ</th>
            <th>الحالة</th>
            <th>المرجع</th>
            <th>التكلفة (شراء)</th>
        </tr>
    </thead>
    <tbody>
        @foreach(($group->scenarios ?? []) as $i => $s)
            @php $buy = (float)($s['usd_buy'] ?? 0); @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $s['date'] ?? '-' }}</td>
                <td>{{ $s['status'] ?? '-' }}</td>
                <td>{{ $s['note'] ?? '-' }}</td>
                <td>{{ number_format($buy,2) }} {{ $currency }}</td>
            </tr>
        @endforeach
        @if(empty($group->scenarios))
            <tr><td colspan="5">لا توجد تفاصيل.</td></tr>
        @endif
    </tbody>
</table>

<div class="summary">
    <div><strong>Subtotal (Net to Provider):</strong> {{ number_format($base, 2) }} {{ $currency }}</div>
    @if($hasTax)
        <div><strong>TAX:</strong> {{ number_format($tax, 2) }} {{ $currency }}</div>
    @endif
    <div><strong>Grand Total:</strong> {{ number_format($net, 2) }} {{ $currency }}</div>
    <div><strong>Only:</strong> {{ ucwords(NumberToWords::convert($net)) }} {{ $currency }}</div>
</div>

<div class="sign">
    <div>
        Approved by / توقيع المزوّد
        <div class="line"></div>
    </div>
    <div>
        Prepared by: {{ $invoice->user->name ?? (Auth::user()->name ?? 'Staff') }}
        <div class="line"></div>
    </div>
</div>

<div style="margin-top:12px;font-size:11px;color:#555;display:flex;justify-content:space-between;">
    <div>Printed by: {{ $invoice->user->name ?? (Auth::user()->name ?? 'Staff') }}</div>
    <div>Printed at: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}</div>
</div>

</body>
</html>
