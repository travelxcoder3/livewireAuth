@php
    use Carbon\Carbon;

    // شعار الوكالة الحالية
    $agency     = Auth::user()?->agency;
    $logoPath   = $agency && $agency->logo ? storage_path('app/public/'.$agency->logo) : null;
    $logoData   = ($logoPath && file_exists($logoPath)) ? base64_encode(file_get_contents($logoPath)) : null;
    $logoMime   = ($logoPath && file_exists($logoPath)) ? mime_content_type($logoPath) : 'image/png';

    $from = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : '—';
    $to   = $endDate   ? Carbon::parse($endDate)->format('Y-m-d')   : '—';
@endphp
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>كشف عمليات الموظف - {{ $employee?->name }}</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 13px; }
        .header {
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; position: relative;
        }
        .logo { width: 140px; height: 80px; object-fit: contain; }
        .title {
            font-size: 20px; font-weight: bold; text-align: center;
            position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
            white-space: nowrap;
        }
        .sub { font-size: 12px; color: #555; text-align: center; margin-top: 6px; }
        .chip { display:inline-block; padding: 3px 8px; border: 1px solid #ddd; border-radius: 6px; margin: 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #aaa; padding: 6px; text-align: center; }
        th { background-color: #eee; font-weight: bold; }
        .section { margin-top: 18px; font-weight: 700; }
        .footer { text-align: center; margin-top: 18px; font-size: 11px; color: #666; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        @if ($logoData)
            <img src="data:{{ $logoMime }};base64,{{ $logoData }}" class="logo" alt="logo">
        @else
            <div class="logo"></div>
        @endif
        <div style="width:140px;"></div>
        <div class="title">كشف عمليات الموظف</div>
    </div>

    {{-- معلومات عامة --}}
    <div class="sub">
        <span class="chip">الموظف: {{ $employee?->name ?? '—' }}</span>
        <span class="chip">الفترة: {{ $from }} — {{ $to }}</span>
        <span class="chip">العملة: {{ $currency ?? 'USD' }}</span>
    </div>

    {{-- تفصيلي حسب الخدمة --}}
    <div class="section">تفصيلي حسب الخدمة</div>
    <table>
        <thead>
            <tr>
                <th>الخدمة</th>
                <th>عدد</th>
                <th>البيع</th>
                <th>الشراء</th>
                <th>الربح</th>
                <th>العمولة</th>
                <th>المستحق</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($byService as $serviceId => $row)
            <tr>
                <td>{{ $row['firstRow']?->service?->label ?? '—' }}</td>
                <td>{{ $row['count'] }}</td>
                <td>{{ number_format($row['sell'],2) }}</td>
                <td>{{ number_format($row['buy'],2) }}</td>
                <td>{{ number_format($row['profit'],2) }}</td>
                <td>{{ number_format($row['commission'],2) }}</td>
                <td>{{ number_format($row['remaining'],2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">لا توجد بيانات</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- تفصيلي حسب الشهر --}}
    <div class="section">تفصيلي حسب الشهر</div>
    <table>
        <thead>
            <tr>
                <th>الشهر</th>
                <th>عدد</th>
                <th>البيع</th>
                <th>الشراء</th>
                <th>الربح</th>
                <th>العمولة</th>
                <th>المستحق</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($byMonth as $ym => $row)
            <tr>
                <td>{{ $ym }}</td>
                <td>{{ $row['count'] }}</td>
                <td>{{ number_format($row['sell'],2) }}</td>
                <td>{{ number_format($row['buy'],2) }}</td>
                <td>{{ number_format($row['profit'],2) }}</td>
                <td>{{ number_format($row['commission'],2) }}</td>
                <td>{{ number_format($row['remaining'],2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">لا توجد بيانات</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- جدول العمليات التفصيلي --}}
    <div class="section">العمليات</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>تاريخ البيع</th>
                <th>الخدمة</th>
                <th>المزوّد</th>
                <th>المستفيد</th>
                <th>PNR</th>
                <th>المرجع</th>
                <th>البيع</th>
                <th>الشراء</th>
                <th>الربح</th>
                <th>المدفوع</th>
                <th>المتبقي</th>
            </tr>
        </thead>
        <tbody>
        @php $i = 1; @endphp
        @forelse($sales as $s)
            @php
                $sell = (float)($s->usd_sell ?? 0);
                $buy  = (float)($s->usd_buy  ?? 0);
                $paid = in_array($s->status, ['Refund-Full','Refund-Partial'])
                        ? 0
                        : (float)($s->amount_paid ?? 0) + (float)($s->collections_sum_amount ?? $s->collections?->sum('amount') ?? 0);
                $remain = $sell - $paid;
            @endphp
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $s->sale_date ? Carbon::parse($s->sale_date)->format('Y-m-d') : '—' }}</td>
                <td>{{ $s->service?->label ?? '—' }}</td>
                <td>{{ $s->provider?->name ?? '—' }}</td>
                <td>{{ $s->beneficiary_name ?? '—' }}</td>
                <td>{{ $s->pnr ?? '—' }}</td>
                <td>{{ $s->reference ?? '—' }}</td>
                <td>{{ number_format($sell,2) }}</td>
                <td>{{ number_format($buy,2) }}</td>
                <td>{{ number_format($sell - $buy,2) }}</td>
                <td>{{ number_format($paid,2) }}</td>
                <td>{{ number_format($remain,2) }}</td>
            </tr>
        @empty
            <tr><td colspan="12">لا توجد عمليات</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        تاريخ الإنشاء: {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>