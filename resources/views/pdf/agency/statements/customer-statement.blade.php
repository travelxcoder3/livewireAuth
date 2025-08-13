@php
    $currency = Auth::user()->agency->currency ?? 'USD';
    $money = fn($v) => number_format((float)$v, 2).' '.$currency;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>كشف حساب – {{ $customer->name }}</title>
<style>
  @page { size: A4; margin: 14mm 10mm; }
  body{ font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color:#111827; line-height:1.55; }
  h1,h2{ margin:0 0 8px; font-weight:800; }
  .muted{ color:#6b7280; font-size:12px; }
  table{ width:100%; border-collapse:collapse; }
  th,td{ border:1px solid #e5e7eb; padding:6px 8px; font-size:12px; text-align:right; }
  th{ background:#f3f4f6; font-weight:700; }
  .blue{ color:#2563eb; } .green{ color:#16a34a; } .red{ color:#dc2626; }
  .head{ text-align:center; border-bottom:3px solid #059669; padding-bottom:8px; margin-bottom:12px; }
  .brand{ display:flex; align-items:center; justify-content:center; gap:12px; }
  .brand img{ height:48px; }
  .title{ text-align:center; margin:6px 0 10px; }
  .section{ margin:10px 0 6px; font-weight:700; }
</style>
</head>
<body>

<div class="head">
  <div class="brand">
    @if($logoDataUrl)<img src="{{ $logoDataUrl }}" alt="logo">@endif
    <div style="font-size:18px; font-weight:800;">{{ $agency->name ?? 'Agency' }}</div>
  </div>
</div>

<div class="title">
  <h2>كشف حساب للعميل: {{ $customer->name }}</h2>
  <div class="muted">تاريخ التوليد: {{ now()->format('Y-m-d') }}</div>
</div>

<div class="section">تفاصيل العمليات</div>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>تاريخ الخدمة</th>
      <th>الوصف</th>
      <th>عليه</th>
      <th>له</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rows as $r)
      <tr>
        <td>{{ $r['no'] }}</td>
        <td>{{ $r['date'] }}</td>
        <td>{{ $r['desc'] }}</td>
        <td class="blue">{{ $money($r['debit']) }}</td>
        <td class="green">{{ $money($r['credit']) }}</td>
      </tr>
    @empty
      <tr><td colspan="7" class="muted" style="text-align:center;">لا توجد بيانات.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="section">الملخص</div>
<table>
  <thead>
    <tr>
      <th>عدد الصفوف</th>
      <th>إجمالي عليه</th>
      <th>إجمالي له</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>{{ $totals['count'] }}</td>
      <td class="blue">{{ $money($totals['debit']) }}</td>
      <td class="green">{{ $money($totals['credit']) }}</td>
    </tr>
  </tbody>
</table>

</body>
</html>
