@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use App\Helpers\NumberToWords;

    $themeName      = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors         = ThemeService::getCurrentThemeColors($themeName);

    $agencyCurrency = $invoice->agency->currency ?? (Auth::user()?->agency?->currency ?? 'USD');

    // شعار الوكالة (من أول سطر بيع)
    $saleExample = $invoice->sales->first();
    $logoPath = $saleExample && $saleExample->agency && $saleExample->agency->logo
        ? storage_path('app/public/' . $saleExample->agency->logo)
        : null;
    $logoData = $logoPath && file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    $mime     = $logoPath && file_exists($logoPath) ? mime_content_type($logoPath) : null;

    // إجماليات (المخزن يغلّب، وإلا نحسب)
    $subtotal = (float) ($invoice->subtotal
        ?? $invoice->sales->sum(fn($s) => (float) ($s->pivot->base_amount ?? $s->usd_sell)));
    $taxTotal = (float) ($invoice->tax_total ?? 0);
    $grand    = (float) ($invoice->grand_total ?? ($subtotal + $taxTotal));
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة مجمعة</title>
    <style>
        body{font-family:'Tajawal',sans-serif;direction:rtl;font-size:13px;color:#000;padding:30px}
        .title{text-align:center;font-size:20px;font-weight:bold;margin-bottom:20px;color:rgb({{ $colors['primary-600'] }})}
        .top{display:flex;justify-content:space-between;margin-bottom:10px;font-size:12px}
        .details{display:flex;justify-content:space-between;font-size:13px;margin-bottom:20px}
        table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:15px}
        th,td{border:1px solid #aaa;padding:8px;text-align:center}
        .tfoot th,.tfoot td{font-weight:bold;background:#f7f7f7}
        .summary{font-size:12px;line-height:1.6;margin-top:10px}
        .sign{display:flex;justify-content:space-between;margin-top:40px;font-size:13px}
        .line{margin-top:30px;border-top:1px solid #ccc}
    </style>
</head>
<body>

    <div class="title">فاتورة مجمعة</div>

    @if($logoData && $mime)
        <div style="text-align:center;margin-bottom:20px;">
            <img src="data:{{ $mime }};base64,{{ $logoData }}" alt="شعار الوكالة" style="width:140px;height:70px;object-fit:contain;">
        </div>
    @endif

    <div class="top">
        <div>
            <strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
            <strong>Date:</strong> {{ $invoice->date }}<br>
            <strong>Client:</strong> {{ $invoice->entity_name }}
        </div>
        <div style="text-align:left;">
            <strong>{{ strtoupper($invoice->agency->name ?? 'AGENCY') }}</strong><br>
            {{ $invoice->agency->address ?? 'العنوان غير متوفر' }}<br>
            {{ $invoice->agency->phone ?? '' }}<br>
            {{ $invoice->agency->email ?? '' }}<br>
            {{ !empty($invoice->agency->tax_number) ? 'VAT: '.$invoice->agency->tax_number : '' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>اسم المستفيد</th>
                <th>الخدمة</th>
                <th>PNR</th>
                <th>المرجع</th>
                <th>المبلغ (صافي)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->sales as $sale)
                @php $lineBase = (float) ($sale->pivot->base_amount ?? $sale->usd_sell); @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $sale->beneficiary_name }}</td>
                    <td>{{ $sale->service->label ?? '-' }}</td>
                    <td>{{ $sale->pnr ?? '-' }}</td>
                    <td>{{ $sale->reference ?? '-' }}</td>
                    <td>{{ number_format($lineBase, 2) }} {{ $agencyCurrency }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="tfoot">
            <tr>
                <td colspan="5" style="text-align:left;">الإجمالي (صافي قبل الضريبة)</td>
                <td>{{ number_format($subtotal, 2) }} {{ $agencyCurrency }}</td>
            </tr>
            <tr>
                <td colspan="5" style="text-align:left;">الضريبة</td>
                <td>{{ number_format($taxTotal, 2) }} {{ $agencyCurrency }}</td>
            </tr>
            <tr>
                <td colspan="5" style="text-align:left;">الإجمالي النهائي</td>
                <td>{{ number_format($grand, 2) }} {{ $agencyCurrency }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <div><strong>Currency:</strong> {{ $agencyCurrency }}</div>
        @if(abs($taxTotal) > 0.00001)
            <div><strong>TAX:</strong> {{ number_format($taxTotal, 2) }}</div>
        @endif
        <div><strong>Net:</strong> {{ number_format($subtotal, 2) }}</div>
        <div><strong>Gross:</strong> {{ number_format($grand, 2) }}</div>
        <div><strong>Only:</strong> {{ ucwords(NumberToWords::convert($grand)) }} {{ $agencyCurrency }}</div>
    </div>

    <div class="sign">
        <div>Approved by / توقيع العميل<div class="line"></div></div>
        <div>Prepared by: {{ $invoice->user->name ?? (Auth::user()->name ?? 'Staff') }}<div class="line"></div></div>
    </div>

    <div style="margin-top:12px;font-size:11px;color:#555;display:flex;justify-content:space-between;">
        <div>Printed by: {{ $invoice->user->name ?? (Auth::user()->name ?? 'Staff') }}</div>
        <div>Printed at: {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}</div>
    </div>

</body>
</html>
