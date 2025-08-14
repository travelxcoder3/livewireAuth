@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Log;

    // اتجاه + الوكالة الأولى
    $dir    = 'rtl';
    $agency = $agency ?? $sales->first()?->agency;

    // ألوان الثيم (افتراضي عند غياب $colors)
    $colors = isset($colors) && is_array($colors) ? $colors : [
        'primary-100' => '209,250,229',
        'primary-500' => '16,185,129',
        'primary-600' => '5,150,105',
    ];

    // العملة و دالة تنسيق
    $currency = $agency?->currency ?? 'USD';
    $money    = fn($v) => number_format((float)$v, 2) . ' ' . $currency;

    // الشعار → data URL
    $logoDataUrl = null;
    if ($agency && !empty($agency->logo)) {
        $logoPath = storage_path('app/public/' . ltrim($agency->logo, '/'));
        if (file_exists($logoPath)) {
            try {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $logoDataUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            } catch (\Throwable $e) {
                Log::error('Error reading logo: '.$e->getMessage());
            }
        } else {
            Log::error('Logo file not found: '.$logoPath);
        }
    }

    // إجماليات بسيطة
    $totalSalesAmount = (float) $sales->sum('usd_sell');
    $rowsCount        = (int) $sales->count();

    // محولات نصوص بسيطة
    $pretty = function($v) {
        if ($v === null || $v === '') return '-';
        $v = str_replace(['_', '-'], ' ', (string)$v);
        return mb_convert_case($v, MB_CASE_TITLE, "UTF-8");
    };
@endphp

<!DOCTYPE html>
<html lang="ar" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <title>تقرير المبيعات</title>
<style>
    :root{
        --primary-100: {{ $colors['primary-100'] ?? '209,250,229' }};
        --primary-500: {{ $colors['primary-500'] ?? '16,185,129'  }};
        --primary-600: {{ $colors['primary-600'] ?? '5,150,105'   }};
        --border:#e5e7eb; --muted:#6b7280; --heading:#111827;
        --grid:#111; /* حدود الجدول داكنة مثل الحسابات */
    }

    @page { size: A4 landscape; margin: 6mm; }
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color:#111827; line-height:1.55; font-size:13px; margin:0; }
    h1,h2,h3 { margin:0 0 10px; color:var(--heading); font-weight:800; }
    .muted { color: var(--muted); font-size:12px; }
    .hr { border:0; border-top: 2px solid var(--border); margin: 10px 0; }

    .invoice-head {
        text-align:center; padding:10px 0 12px;
        border-bottom:3px solid rgb(var(--primary-600)); margin-bottom:14px;
    }
    .brand { display:flex; align-items:center; justify-content:center; gap:12px; }
    .brand img { height:48px; width:auto; }
    .brand .name { font-size:20px; font-weight:800; color:#111827; }

    .title { text-align:center; margin-top:6px; }
    .title h2 { margin:0; font-size:20px; color:#111827; }

    /* جدول مطابق لتقرير الحسابات */
    table{
      table-layout:fixed; width:100%; border-collapse:collapse;
      border:0.6pt solid var(--grid);
    }
    th,td{
      font-size:9px;  padding:3px 4px; line-height:1.3;
      text-align:right; vertical-align:top;
      white-space:normal;            /* لا تقسيم لكل حرف */
      word-break:normal;             /* لا تكسر العربية */
      overflow-wrap:break-word;      /* اسم طويل فقط */
      border:0.5pt solid var(--grid);
    }
thead th{
  font-size:8px;                /* أصغر */
  line-height:1.2;               /* يقلل ارتفاع الخلية */
  padding:2px 3px;               /* حواف أقل */
  white-space:normal;            /* يسمح بكسر السطر */
  word-break:keep-all;           /* يحافظ على الكلمات العربية */
  border-top:0.6pt solid var(--grid);
  border-bottom:0.6pt solid var(--grid);
}
    tbody tr:nth-child(even){ background:#fcfcfc; }

    .section {
        margin:14px 0 8px; padding:6px 10px;
        border-inline-start:4px solid rgb(var(--primary-600));
        background: rgba(var(--primary-100), .1);
        font-weight:700; color:#111827;
    }
    .card { position:relative; border:1px solid var(--border); border-radius:8px; padding:10px; margin-bottom:12px; background:#fff; }
    .card:before{ content:""; position:absolute; inset-inline-start:0; top:0; bottom:0; width:4px; background:rgb(var(--primary-600)); border-radius:8px 0 0 8px; }
    .footer-note { margin-top:10px; text-align:center; font-size:11px; color:var(--muted); }
</style>

</head>
<body>

    {{-- شعار + اسم الوكالة --}}
    <div class="invoice-head">
        <div class="brand">
            @if(!empty($logoDataUrl))
                <img src="{{ $logoDataUrl }}" alt="Logo">
            @endif
            <div class="name">{{ $agency->name ?? 'اسم الوكالة' }}</div>
        </div>
    </div>

    {{-- عنوان التقرير --}}
    <div class="title">
        <h2>تقرير المبيعات</h2>
        <div class="muted">تاريخ التوليد: {{ now()->format('Y-m-d') }}</div>
    </div>

    <hr class="hr"/>

    {{-- جدول المبيعات --}}
    <div class="card">
        <div class="section">قائمة العمليات</div>
{{-- بعد — استبدل الجسم الجدولي فقط بـ: --}}
@php
  $statusMap = [
    'paid'=>'مدفوع','unpaid'=>'غير مدفوع','issued'=>'تم الإصدار','reissued'=>'أعيد إصداره',
    'refunded'=>'تم الاسترداد','canceled'=>'ملغي','pending'=>'قيد الانتظار','void'=>'ملغي نهائي',
    'Refund-Full'=>'Refund-Full','Refund-Partial'=>'Refund-Partial','Issued'=>'Issued'
  ];
  $nf = fn($v)=>number_format((float)$v,2,'.',',');
@endphp

<table>
  <thead>
    <tr>
      @foreach($fields as $f)
        <th>{{ $headers[$f] ?? $f }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @forelse($sales as $sale)
      <tr>
        @foreach($fields as $f)
          @php $val = data_get($sale, $f); @endphp
          <td class="text-right">
            @switch($formats[$f] ?? null)
              @case('money')  {{ $nf($val) }} {{ $sale->agency->currency ?? 'USD' }} @break
              @case('date')   {{ $val ? \Carbon\Carbon::parse($val)->format('Y-m-d H:i') : '—' }} @break
              @case('status') {{ $statusMap[$val] ?? ($val ?? '—') }} @break
              @default        {{ is_scalar($val) ? $val : (is_array($val)?json_encode($val,JSON_UNESCAPED_UNICODE):'—') }}
            @endswitch
          </td>
        @endforeach
      </tr>
    @empty
      <tr><td colspan="{{ count($fields) }}" style="text-align:center;">لا توجد بيانات</td></tr>
    @endforelse
  </tbody>
</table>

    </div>

    {{-- الملخص الإجمالي --}}
    <div class="card">
        <div class="section">الملخص الإجمالي</div>
        <table>
            <thead>
                <tr>
                    <th>عدد العمليات</th>
                    <th>إجمالي المبيعات</th>
                    <th>المستخدم المُصدِّر</th>
                    <th>بتاريخ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $rowsCount }}</td>
                    <td class="blue">{{ $money($totalSalesAmount) }}</td>
                    <td>{{ auth()->user()->name }}</td>
                    <td>{{ Carbon::now()->translatedFormat('j-n-Y') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer-note">
        تم إنشاء هذا التقرير بواسطة نظام إدارة وكالات السفر – {{ $agency->name ?? '' }}<br>
        {{ Carbon::now()->translatedFormat('Y-m-d H:i:s') }}
    </div>

</body>
</html>
