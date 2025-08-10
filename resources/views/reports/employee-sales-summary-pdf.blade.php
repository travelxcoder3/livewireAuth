<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>كشف تتبع عمليات الموظفين (ملخص)</title>
    <style>
        @page { margin: 30px; }
        html,body { -webkit-print-color-adjust: exact; }
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 13px; }

        .header{
            display:grid;
            grid-template-columns:140px 1fr 140px;
            align-items:center;
            gap:12px;
            border-bottom:1px solid #ccc;
            padding-bottom:10px; margin-bottom:16px;
        }
        .logo { width:140px; height:80px; object-fit:contain; }
        .title{ text-align:center; font-size:20px; font-weight:700; }
        .period { font-size:12px; color:#666; text-align:center; margin-top:6px; }

        table{ width:100%; border-collapse:collapse; margin-top:12px; table-layout:fixed; }
        th, td{ border:1px solid #aaa; padding:6px 8px; text-align:center; white-space:nowrap; vertical-align:middle; }
        th{ background:#eee; font-weight:600; }
        tr{ page-break-inside: avoid; }

        .num{ direction:ltr; unicode-bidi:embed; }
    </style>
</head>
<body>

@php
    $from = $startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : '—';
    $to   = $endDate   ? \Carbon\Carbon::parse($endDate)->format('Y-m-d')   : '—';
    function nf($v){ return number_format((float)$v, 2, '.', ','); }
@endphp

<div class="header">
    @if (!empty($logoData))
        <img src="data:{{ $logoMime ?? 'image/png' }};base64,{{ $logoData }}" class="logo" alt="logo">
    @else
        <div class="logo"></div>
    @endif
    <div class="title">كشف تتبع عمليات الموظفين (ملخص)</div>
    <div></div>
</div>

<div class="period">الفترة: {{ $from }} — {{ $to }}</div>

<table>
    <!-- 9 أعمدة = 100% -->
    <colgroup>
        <col style="width:16%"><col style="width:8%"><col style="width:12%">
        <col style="width:12%"><col style="width:10%">
        <col style="width:13%"><col style="width:13%">
        <col style="width:10%"><col style="width:6%">
    </colgroup>
    <thead>
    <tr>
        <th>اسم الموظف</th>
        <th>عدد العمليات</th>
        <th>إجمالي البيع</th>
        <th>إجمالي الشراء</th>
        <th>الربح</th>
        <th>عمولة (متوقعة)</th>
        <th>عمولة (مستحقة)</th>
        <th>إجمالي المستحق</th>
        <th>العملة</th>
    </tr>
    </thead>
    <tbody>
    @forelse($perEmployee as $row)
        <tr>
            <td>{{ $row['user']?->name ?? '—' }}</td>
            <td class="num">{{ nf($row['count'] ?? 0) }}</td>
            <td class="num">{{ nf($row['sell'] ?? 0) }}</td>
            <td class="num">{{ nf($row['buy'] ?? 0) }}</td>
            <td class="num">{{ nf($row['profit'] ?? 0) }}</td>
            <td class="num">{{ nf($row['employee_commission_expected'] ?? 0) }}</td>
            <td class="num">{{ nf($row['employee_commission_due'] ?? 0) }}</td>
            <td class="num">{{ nf($row['remaining'] ?? 0) }}</td>
            <td>{{ $currency ?? 'USD' }}</td>
        </tr>
    @empty
        <tr><td colspan="9">لا توجد بيانات</td></tr>
    @endforelse
    </tbody>
</table>

<div class="period" style="margin-top:10px">
    إجمالي الموظفين: {{ is_countable($perEmployee ?? []) ? count($perEmployee) : 0 }}<br>
    تاريخ الإنشاء: {{ now()->format('Y-m-d H:i:s') }}
</div>

</body>
</html>
