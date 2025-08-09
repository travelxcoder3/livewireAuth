<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>كشف تتبع عمليات الموظفين (ملخص)</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 13px; }
        .header {
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;
            position: relative;
        }
        .logo { width: 140px; height: 80px; object-fit: contain; }
        .title {
            font-size: 20px; font-weight: bold; text-align: center;
            position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
            white-space: nowrap;
        }
        .period { font-size: 12px; color: #666; text-align: center; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #aaa; padding: 6px; text-align: center; }
        th { background-color: #eee; font-weight: bold; }
        .footer { text-align: center; margin-top: 18px; font-size: 11px; color: #666; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        @if (!empty($logoData))
            <img src="data:{{ $logoMime ?? 'image/png' }};base64,{{ $logoData }}" class="logo" alt="logo">
        @else
            <div class="logo"></div>
        @endif
        <div style="width:140px;"></div> {{-- spacer يمين لتوازن الفلكس --}}
        <div class="title">كشف تتبع عمليات الموظفين (ملخص)</div>
    </div>

    {{-- الفترة --}}
    <div class="period">
        @php
            $from = $startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : '—';
            $to   = $endDate   ? \Carbon\Carbon::parse($endDate)->format('Y-m-d')   : '—';
        @endphp
        الفترة: {{ $from }} — {{ $to }}
    </div>

    {{-- الجدول --}}
    <table>
        <thead>
        <tr>
            <th>اسم الموظف</th>
            <th>عدد العمليات</th>
            <th>إجمالي البيع</th>
            <th>إجمالي الشراء</th>
            <th>الربح</th>
            <th>العمولة</th>
            <th>إجمالي المستحق</th>
            <th>العملة</th>
        </tr>
        </thead>
        <tbody>
        @forelse($perEmployee as $row)
            <tr>
                <td>{{ $row['user']?->name ?? '—' }}</td>
                <td>{{ $row['count'] }}</td>
                <td>{{ number_format($row['sell'],2) }}</td>
                <td>{{ number_format($row['buy'],2) }}</td>
                <td>{{ number_format($row['profit'],2) }}</td>
                <td>{{ number_format($row['commission'],2) }}</td>
                <td>{{ number_format($row['remaining'],2) }}</td>
                <td>{{ $currency ?? 'USD' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">لا توجد بيانات</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        إجمالي الموظفين: {{ count($perEmployee ?? []) }}<br>
        تاريخ الإنشاء: {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>