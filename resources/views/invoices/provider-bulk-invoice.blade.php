@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\NumberToWords;

    /** المدخلات المتوقعة:
     * $provider : App\Models\Provider
     * $invoice  : App\Models\Invoice  (الرقم/التاريخ/الإجماليات)
     * $groups   : Collection|array من مجموعات المزوّد (مثل ProviderInvoiceOverview) لكل سطر
     * $currency : (اختياري)
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

    // الإجماليات
    $subtotal = (float) ($invoice->subtotal ?? collect($groups ?? [])->sum(fn($g)=> (float)($g->net_cost ?? 0)));
    $taxTotal = (float) ($invoice->tax_total ?? 0);
    $grand    = (float) ($invoice->grand_total ?? ($subtotal + $taxTotal));
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة مزوّد مجمعة</title>
    <style>
        body{font-family:'Tajawal',sans-serif;direction:rtl;font-size:13px;color:#000;padding:30px}
        .title{text-align:center;font-size:20px;font-weight:bold;margin-bottom:20px;color:rgb({{ $colors['primary-600'] }})}
        .top{display:flex;justify-content:space-between;margin-bottom:10px;font-size:12px}
        .top div{line-height:1.5}
        table{width:100%;border-collapse:collapse;font-size:12px;margin-top:10px}
        th,td{border:1px solid #aaa;padding:8px;text-align:center}
        .tfoot th,.tfoot td{font-weight:bold;background:#f7f7f7}
        .summary{font-size:12px;line-height:1.6;margin-top:12px}
        .sign{display:flex;justify-content:space-between;margin-top:40px;font-size:13px}
        .line{margin-top:30px;border-top:1px solid #ccc}
    </style>
</head>
<body>

<div class="title">فاتورة مزوّد مجمعة</div>

@if($logoData && $mime)
    <div style="text-align:center;margin-bottom:20px;">
        <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار" style="width:140px;height:70px;object-fit:contain;">
    </div>
@endif

<div class="top">
    <div>
        <strong>Invoice No:</strong> {{ $invoice->invoice_number ?? 'PRV-BULK-'.($provider->id ?? '') }}<br>
        <strong>Date:</strong> {{ $invoice->date ?? now()->toDateString() }}<br>
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

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>المجموعة</th>
            <th>المستفيد</th>
            <th>الخدمة</th>
            <th>أصل التكلفة</th>
            <th>الاستردادات</th>
            <th>الصافي للمزوّد</th>
        </tr>
    </thead>
    <tbody>
        @forelse($groups ?? [] as $i => $g)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $g->group_key ?? '-' }}</td>
                <td>{{ $g->beneficiary_name ?? '-' }}</td>
                <td>{{ $g->service_label ?? '-' }}</td>
                <td>{{ number_format((float)($g->cost_total_true ?? 0),2) }} {{ $currency }}</td>
                <td>{{ number_format((float)($g->refund_total ?? 0),2) }} {{ $currency }}</td>
                <td>{{ number_format((float)($g->net_cost ?? 0),2) }} {{ $currency }}</td>
            </tr>
        @empty
            <tr><td colspan="7">لا توجد عناصر.</td></tr>
        @endforelse
    </tbody>
    <tfoot class="tfoot">
        <tr>
            <td colspan="6" style="text-align:left;">الإجمالي (صافي قبل الضريبة)</td>
            <td>{{ number_format($subtotal, 2) }} {{ $currency }}</td>
        </tr>
        <tr>
            <td colspan="6" style="text-align:left;">الضريبة</td>
            <td>{{ number_format($taxTotal, 2) }} {{ $currency }}</td>
        </tr>
        <tr>
            <td colspan="6" style="text-align:left;">الإجمالي النهائي</td>
            <td>{{ number_format($grand, 2) }} {{ $currency }}</td>
        </tr>
    </tfoot>
</table>

<div class="summary">
    <div><strong>Currency:</strong> {{ $currency }}</div>
    @if(abs($taxTotal) > 0.00001)
        <div><strong>TAX:</strong> {{ number_format($taxTotal, 2) }}</div>
    @endif
    <div><strong>Net:</strong> {{ number_format($subtotal, 2) }}</div>
    <div><strong>Gross:</strong> {{ number_format($grand, 2) }}</div>
    <div><strong>Only:</strong> {{ ucwords(NumberToWords::convert($grand)) }} {{ $currency }}</div>
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
