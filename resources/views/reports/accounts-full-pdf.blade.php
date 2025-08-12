@php
    use App\Services\ThemeService;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;

    // اتجاه + الوكالة
    $dir    = 'rtl';

    // ألوان الثيم (افتراضي عند غياب $colors)
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors    = isset($colors) && is_array($colors)
        ? $colors
        : (ThemeService::getCurrentThemeColors($themeName) ?? [
            'primary-100' => '209,250,229',
            'primary-500' => '16,185,129',
            'primary-600' => '5,150,105',
        ]);

    // العملة + تنسيق
    $currency = $agency?->currency ?? 'USD';
    $money    = fn($v) => number_format((float)$v, 2) . ' ' . $currency;

    // الشعار → data URL (نفس أسلوب التقارير السابقة)
    $logoDataUrl = null;
    if (!empty($logoData) && !empty($mime)) {
        $logoDataUrl = 'data:' . $mime . ';base64,' . $logoData;
    } elseif ($agency && !empty($agency->logo)) {
        $path = storage_path('app/public/' . ltrim($agency->logo, '/'));
        if (file_exists($path)) {
            try {
                $m = mime_content_type($path) ?: 'image/png';
                $logoDataUrl = 'data:' . $m . ';base64,' . base64_encode(file_get_contents($path));
            } catch (\Throwable $e) {
                Log::error('Logo read error: '.$e->getMessage());
            }
        } else {
            Log::error('Logo file does not exist at path: '.$path);
        }
    }

    // إجماليات
    $rowsCount   = (int) $sales->count();
    $totalAmount = (float) $sales->sum('usd_sell');

    // تنعيم نصوص (customer_via / payment_method... إلخ) عند الحاجة
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
    <title>تقرير الحسابات</title>
    <style>
        :root{
            --primary-100: {{ $colors['primary-100'] ?? '209,250,229' }}; /* rgb */
            --primary-500: {{ $colors['primary-500'] ?? '16,185,129'  }}; /* rgb */
            --primary-600: {{ $colors['primary-600'] ?? '5,150,105'   }}; /* rgb */

            --border: #e5e7eb;
            --muted: #6b7280;
            --heading: #111827;
        }

        @page { size: A4 landscape; margin: 10mm; }  
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color:#111827; line-height: 1.55; font-size: 13px; }
        h1,h2,h3 { margin:0 0 10px; color: var(--heading); font-weight: 800; }
        .muted { color: var(--muted); font-size: 12px; }
        .hr { border:0; border-top: 2px solid var(--border); margin: 10px 0; }

        /* الهيدر الموحّد: شعار + اسم الوكالة */
        .invoice-head {
            text-align: center;
            padding: 10px 0 12px;
            border-bottom: 3px solid rgb(var(--primary-600));
            margin-bottom: 14px;
        }
        .brand { display:flex; align-items:center; justify-content:center; gap:12px; }
        .brand img { height: 48px; width:auto; }
        .brand .name { font-size: 20px; font-weight:800; color: #111827; }

        .title { text-align:center; margin-top: 6px; }
        .title h2 { margin:0; font-size: 20px; color: #111827; }
        .title .muted { margin-top: 2px; }

:root{ --grid:#111; }                     /* لون حدود داكن */
@page { size: A4 landscape; margin: 10mm; }
*{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }

table{
  table-layout:fixed; width:100%; border-collapse:collapse;
  border:0.6pt solid var(--grid);         /* إطار الجدول */
}
th,td{
  font-size:10px; padding:4px 6px; line-height:1.4;
  white-space:normal; word-break: normal; overflow-wrap: break-word;
  vertical-align:top; text-align:right;
  border:0.5pt solid var(--grid);         /* حدود الأعمدة والصفوف */
}
thead th{
  font-size:11px; border-top:0.6pt solid var(--grid); border-bottom:0.6pt solid var(--grid);
}
tbody tr:nth-child(even){ background:#fcfcfc; } /* اختياري للتباين */


        .blue { color:#2563eb; }
        .green{ color:#16a34a; }
        .red  { color:#dc2626; }

        .section {
            margin: 14px 0 8px;
            padding: 6px 10px;
            border-inline-start: 4px solid rgb(var(--primary-600));
            background: rgba(var(--primary-100), .1);
            font-weight: 700;
            color: #111827;
        }

        .card {
            position: relative;
            border:1px solid var(--border);
            border-radius:8px;
            padding:10px;
            margin-bottom:12px;
            background: #fff;
        }
        .card:before{
            content:"";
            position:absolute;
            inset-inline-start:0; top:0; bottom:0;
            width:4px;
            background: rgb(var(--primary-600));
            border-radius:8px 0 0 8px;
        }

        .footer-note { margin-top: 10px; text-align: center; font-size: 11px; color: var(--muted); }
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
        <h2>تقرير الحسابات</h2>
        <div class="muted">تاريخ التوليد: {{ now()->format('Y-m-d') }}</div>
    </div>

    <hr class="hr"/>

    {{-- جدول العمليات --}}
    <div class="card">
        <div class="section">قائمة العمليات</div>
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
    @foreach($sales as $sale)
      <tr>
        @foreach($fields as $f)
          @php $val = data_get($sale, $f); @endphp
          <td class="text-right">
            @switch($formats[$f] ?? null)
              @case('money')  {{ $nf($val) }} {{ $currency }} @break
              @case('date')   {{ $val ? \Carbon\Carbon::parse($val)->format('Y-m-d H:i') : '—' }} @break
              @case('status') {{ $statusMap[$val] ?? ($val ?? '—') }} @break
              @default        {{ $val ?? '—' }}
            @endswitch
          </td>
        @endforeach
      </tr>
    @endforeach
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
                    <th>إجمالي المبالغ</th>
                    <th>المستخدم المُصدِّر</th>
                    <th>بتاريخ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $rowsCount }}</td>
                    <td class="blue">{{ $money($totalAmount) }}</td>
                    <td>{{ auth()->user()->name }}</td>
                    <td>{{ Carbon::now()->translatedFormat('Y-m-d H:i') }}</td>
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
