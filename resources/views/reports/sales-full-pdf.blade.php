@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Log;

    // اتجاه + الوكالة الأولى
    $dir    = 'rtl';
    $agency = $sales->first()?->agency;

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
            --primary-100: {{ $colors['primary-100'] ?? '209,250,229' }}; /* rgb */
            --primary-500: {{ $colors['primary-500'] ?? '16,185,129'  }}; /* rgb */
            --primary-600: {{ $colors['primary-600'] ?? '5,150,105'   }}; /* rgb */

            --border: #e5e7eb;
            --muted: #6b7280;
            --heading: #111827;
        }

        @page { size: A4; margin: 14mm 10mm; }        /* نعتمد الهامش من CSS فقط */
        * { box-sizing: border-box; }

        html, body { margin:0; padding:0; }           /* ألغِ أي هوامش افتراضية */
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color:#111827; line-height: 1.55; font-size: 13px; }
        h1,h2,h3 { margin:0 0 10px; color: var(--heading); font-weight: 800; }
        .muted { color: var(--muted); font-size: 12px; }
        .hr { border:0; border-top: 2px solid var(--border); margin: 10px 0; }

        /* الهيدر: شعار + اسم الوكالة */
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
        /* غلاف يضمن ألا يتجاوز المحتوى عرض الصفحة بعد الهوامش: 210mm - 10 - 10 = 190mm */
        .page { max-width: 190mm; margin: 0 auto; }

        table { width:100%; border-collapse:collapse; margin-top:8px; table-layout: fixed; } /* يمنع تمدد الأعمدة */
        th,td {
            border:1px solid var(--border);
            padding:6px 8px;
            font-size:12px;
            text-align:right;
            word-break: break-word;     /* يكسر النصوص الطويلة */
            overflow-wrap: anywhere;    /* يكسر الأرقام/الروابط */
        }

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
            overflow: hidden;           /* امنع أي تجاوز عرضي من العناصر الداخلية */
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
        <h2>تقرير المبيعات</h2>
        <div class="muted">تاريخ التوليد: {{ now()->format('Y-m-d') }}</div>
    </div>

    <hr class="hr"/>

    {{-- جدول المبيعات --}}
    <div class="card">
        <div class="section">قائمة العمليات</div>
        <table>
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الموظف</th>
                    <th>نوع الخدمة</th>
                    <th>المزود</th>
                    <th>حساب العميل</th>
                    <th>المرجع</th>
                    <th>PNR</th>
                    <th>العميل عبر</th>
                    <th>طريقة/حالة الدفع</th>
                    <th>المبلغ ({{ $currency }})</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr>
                        <td>{{ optional($sale->created_at)->format('Y-m-d') ?? '-' }}</td>
                        <td>{{ $sale->user->name ?? '-' }}</td>
                        <td>{{ $sale->service->label ?? '-' }}</td>
                        <td>{{ $sale->provider->name ?? '-' }}</td>
                        <td>{{ $sale->customer->name ?? '-' }}</td>
                        <td>{{ $sale->reference ?? '-' }}</td>
                        <td>{{ $sale->pnr ?? '-' }}</td>
                        <td>{{ $pretty($sale->customer_via) }}</td>
                        <td>{{ $pretty($sale->payment_method) }}</td>
                        <td class="green" style="text-align:left;">{{ number_format((float)$sale->usd_sell, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="muted" style="text-align:center;">لا توجد بيانات</td>
                    </tr>
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
