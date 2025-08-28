@php
    $colorVar = 'rgb(var(--primary-600))';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>فواتير المزوّد</title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    * { font-family: DejaVu Sans, Arial, sans-serif; }
    body { font-size: 12px; color:#111; }
    h1 { font-size: 18px; margin:0 0 10px; color:#0f172a;}
    .meta { margin-bottom:14px; }
    .card { border:1px solid #e5e7eb; border-radius:12px; padding:12px; }
    table { width:100%; border-collapse:collapse; }
    thead { background:#f3f4f6; color:#374151; }
    th, td { border-bottom:1px solid #e5e7eb; padding:6px 8px; text-align:right; }
    .text-blue  { color:#1d4ed8; }
    .text-rose  { color:#be123c; }
    .text-emer  { color:#047857; font-weight:600; }
    .muted { color:#6b7280; }
</style>
</head>
<body>

<h1>كشف حساب مفصل للمزوّد: {{ $provider->name }}</h1>
<div class="meta muted">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }} • العملة: {{ $currency }}</div>

<div class="card">
<table>
    <thead>
        <tr>
            <th>اسم المستفيد</th>
            <th>تاريخ البيع</th>
            <th>الخدمة</th>
            <th>تكلفة الخدمة (أصل)</th>
            <th>الاستردادات</th>
            <th>صافي مستحق للمزوّد</th>
        </tr>
    </thead>
    <tbody>
        @forelse($groups as $g)
            <tr>
                <td>{{ $g->beneficiary_name }}</td>
                <td>{{ $g->sale_date }}</td>
                <td>{{ $g->service_label }}</td>
                <td class="text-blue">{{ number_format($g->cost_total_true, 2) }} {{ $currency }}</td>
                <td class="text-rose">{{ number_format($g->refund_total, 2) }} {{ $currency }}</td>
                <td class="text-emer">{{ number_format($g->net_cost, 2) }} {{ $currency }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted" style="text-align:center;padding:14px">لا توجد بيانات</td></tr>
        @endforelse
    </tbody>
</table>
</div>

</body>
</html>
