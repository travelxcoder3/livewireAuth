@php
  $currency = Auth::user()->agency->currency ?? 'USD';
  $money = fn($v) => number_format((float)$v, 2).' '.$currency;
  $sumDebit  = $totals['debit']  ?? collect($rows)->sum('debit');
  $sumCredit = $totals['credit'] ?? collect($rows)->sum('credit');
  $net = (float)$sumCredit - (float)$sumDebit; // له − عليه
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8"><title>كشف حساب مزوّد</title>
<style>
  @page{size:A4;margin:14mm 10mm}body{font-family:DejaVu Sans, Tahoma, Arial; color:#111827}
  table{width:100%;border-collapse:collapse}th,td{border:1px solid #e5e7eb;padding:6px 8px;font-size:12px;text-align:right}
  th{background:#f3f4f6} .green{color:#16a34a} .red{color:#dc2626}
</style>
</head>
<body>
<div style="text-align:center;border-bottom:3px solid #059669;padding-bottom:8px;margin-bottom:12px">
  @if($logoDataUrl)<img src="{{ $logoDataUrl }}" style="height:48px">@endif
  <div style="font-weight:800">{{ $agency->name ?? 'Agency' }}</div>
</div>
<h2 style="text-align:center">كشف حساب المزوّد: {{ $provider->name }}</h2>

<table><thead><tr><th>#</th><th>التاريخ</th><th>الوصف</th><th>له</th><th>عليه</th></tr></thead>
<tbody>
@forelse($rows as $r)
<tr><td>{{ $r['no'] }}</td><td>{{ $r['date'] }}</td><td>{{ $r['desc'] }}</td>
<td class="green">{{ $money($r['credit']) }}</td><td class="red">{{ $money($r['debit']) }}</td></tr>
@empty <tr><td colspan="5" style="text-align:center;color:#6b7280">لا توجد بيانات.</td></tr>
@endforelse
</tbody></table>

<table style="margin-top:10px"><thead><tr><th>عدد الصفوف</th><th>إجمالي له</th><th>إجمالي عليه</th><th>الصافي (له − عليه)</th></tr></thead>
<tbody><tr>
<td>{{ $totals['count'] }}</td>
<td class="green">{{ $money($sumCredit) }}</td>
<td class="red">{{ $money($sumDebit) }}</td>
<td class="{{ $net>=0?'green':'red' }}">{{ $money($net) }}</td>
</tr></tbody></table>
</body></html>
