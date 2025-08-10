@php
    use Carbon\Carbon;

    $agency     = Auth::user()?->agency;
    $logoPath   = $agency && $agency->logo ? storage_path('app/public/'.$agency->logo) : null;
    $logoData   = ($logoPath && file_exists($logoPath)) ? base64_encode(file_get_contents($logoPath)) : null;
    $logoMime   = ($logoPath && file_exists($logoPath)) ? mime_content_type($logoPath) : 'image/png';

    $from = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : '—';
    $to   = $endDate   ? Carbon::parse($endDate)->format('Y-m-d')   : '—';

    // دالة قصيرة لتنسيق الأرقام بنفس الطريقة دائماً
    function nf($v) { return number_format((float)$v, 2, '.', ','); }
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>كشف عمليات الموظف - {{ $employee?->name }}</title>
    <style>
        @page { margin: 30px; }
        html,body { -webkit-print-color-adjust: exact; }
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; font-size: 13px; }

        /* ===== Header ===== */
        .header{
            display:grid;
            grid-template-columns:140px 1fr 140px;
            align-items:center;
            gap:12px;
            border-bottom:1px solid #ccc;
            padding-bottom:10px; margin-bottom:18px;
        }
        .logo{ width:140px; height:80px; object-fit:contain; }
        .title{ text-align:center; font-size:20px; font-weight:700; }

        .sub{ font-size:12px; color:#555; text-align:center; margin-top:6px; }
        .chip{ display:inline-block; padding:3px 8px; border:1px solid #ddd; border-radius:6px; margin:0 4px; }

        /* ===== Tables ===== */
        table{ width:100%; border-collapse:collapse; margin-top:12px; table-layout:fixed; }
        th,td{ border:1px solid #aaa; padding:6px 8px; text-align:center; white-space:nowrap; vertical-align:middle; }
        th{ background:#eee; font-weight:700; }
        tr{ page-break-inside: avoid; }

        /* أرقام LTR كي لا تنعكس الفواصل */
        .num{ direction:ltr; unicode-bidi:embed; }

        .section{ margin-top:16px; font-weight:700; }
        .footer{ text-align:center; margin-top:16px; font-size:11px; color:#666; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        @if ($logoData)
            <img src="data:{{ $logoMime }};base64,{{ $logoData }}" class="logo" alt="logo">
        @else
            <div class="logo"></div>
        @endif>
        <div class="title">كشف عمليات الموظف</div>
        <div></div>
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
        <colgroup>
            <col style="width:18%"><col style="width:8%"><col style="width:12%">
            <col style="width:12%"><col style="width:12%">
            <col style="width:14%"><col style="width:14%"><col style="width:10%">
        </colgroup>
        <thead>
        <tr>
            <th>الخدمة</th>
            <th>عدد</th>
            <th>البيع</th>
            <th>الشراء</th>
            <th>الربح</th>
            <th>عمولةالمتوقعة</th>
            <th>عمولةالمستحقة</th>
            <th>المستحق</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($byService as $serviceId => $row)
            <tr>
                <td>{{ $row['firstRow']?->service?->label ?? '—' }}</td>
                <td class="num">{{ nf($row['count']) }}</td>
                <td class="num">{{ nf($row['sell']) }}</td>
                <td class="num">{{ nf($row['buy']) }}</td>
                <td class="num">{{ nf($row['profit']) }}</td>
                <td class="num">{{ nf($row['employee_commission_expected'] ?? 0) }}</td>
                <td class="num">{{ nf($row['employee_commission_due'] ?? 0) }}</td>
                <td class="num">{{ nf($row['remaining']) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">لا توجد بيانات</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- تفصيلي حسب الشهر --}}
    <div class="section">تفصيلي حسب الشهر</div>
    <table>
        <colgroup>
            <col style="width:12%"><col style="width:8%"><col style="width:14%">
            <col style="width:14%"><col style="width:14%">
            <col style="width:14%"><col style="width:14%"><col style="width:10%">
        </colgroup>
        <thead>
        <tr>
            <th>الشهر</th>
            <th>عدد</th>
            <th>البيع</th>
            <th>الشراء</th>
            <th>الربح</th>
            <th>عمولةالمتوقعة</th>
            <th>عمولةالمستحقة</th>
            <th>المستحق</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($byMonth as $ym => $row)
            <tr>
                <td>{{ $ym }}</td>
                <td class="num">{{ nf($row['count']) }}</td>
                <td class="num">{{ nf($row['sell']) }}</td>
                <td class="num">{{ nf($row['buy']) }}</td>
                <td class="num">{{ nf($row['profit']) }}</td>
                <td class="num">{{ nf($row['employee_commission_expected'] ?? 0) }}</td>
                <td class="num">{{ nf($row['employee_commission_due'] ?? 0) }}</td>
                <td class="num">{{ nf($row['remaining']) }}</td>
            </tr>
        @empty
            <tr><td colspan="8">لا توجد بيانات</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- جدول العمليات التفصيلي --}}
    <div class="section">العمليات</div>
    <table>
        <colgroup>
            <col style="width:4%"><col style="width:10%"><col style="width:12%"><col style="width:12%">
            <col style="width:12%"><col style="width:8%"><col style="width:10%">
            <col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:8%">
        </colgroup>
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
                <td class="num">{{ nf($i++) }}</td>
                <td>{{ $s->sale_date ? Carbon::parse($s->sale_date)->format('Y-m-d') : '—' }}</td>
                <td>{{ $s->service?->label ?? '—' }}</td>
                <td>{{ $s->provider?->name ?? '—' }}</td>
                <td>{{ $s->beneficiary_name ?? '—' }}</td>
                <td>{{ $s->pnr ?? '—' }}</td>
                <td>{{ $s->reference ?? '—' }}</td>
                <td class="num">{{ nf($sell) }}</td>
                <td class="num">{{ nf($buy) }}</td>
                <td class="num">{{ nf($sell - $buy) }}</td>
                <td class="num">{{ nf($paid) }}</td>
                <td class="num">{{ nf($remain) }}</td>
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
