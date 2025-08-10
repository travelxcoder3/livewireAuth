@php
    use App\Services\ThemeService;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;
    use Carbon\Carbon;

    // اتجاه + الوكالة
    $dir    = 'rtl';
    $agency = $sale->agency ?? null;

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
    $currency = $agency->currency ?? 'USD';
    $money    = fn($v) => number_format((float)$v, 2) . ' ' . $currency;

    // الشعار → data URL (مطابق للتقارير الأخرى)
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

    // قيَم مالية أساسية
    $usdSell     = (float) ($sale->usd_sell      ?? 0);
    $usdBuy      = (float) ($sale->usd_buy       ?? 0);
    $amountPaid  = (float) ($sale->amount_paid   ?? 0);
    $grossProfit = $usdSell - $usdBuy;
    $remaining   = max($usdSell - $amountPaid, 0);
@endphp

<!DOCTYPE html>
<html lang="ar" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الحساب</title>
    <style>
        :root{
            --primary-100: {{ $colors['primary-100'] ?? '209,250,229' }}; /* rgb */
            --primary-500: {{ $colors['primary-500'] ?? '16,185,129'  }}; /* rgb */
            --primary-600: {{ $colors['primary-600'] ?? '5,150,105'   }}; /* rgb */

            --border: #e5e7eb;
            --muted: #6b7280;
            --heading: #111827;
        }

        @page { size: A4; margin: 14mm 10mm; }
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

        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th,td { border:1px solid var(--border); padding:6px 8px; font-size:12px; text-align:right; }
        th {
            background: rgba(var(--primary-100), .1);
            color: #111827;
            font-weight: 700;
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
        }
        .card:before{
            content:"";
            position:absolute;
            inset-inline-start:0; top:0; bottom:0;
            width:4px;
            background: rgb(var(--primary-600));
            border-radius:8px 0 0 8px;
        }

        .totals table td, .totals table th { border:none; }
        .totals table { border:1px solid var(--border); }
        .totals thead th {
            background: rgba(var(--primary-100), .1);
            color:#111827;
            border-bottom:1px solid var(--border);
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
        <h2>تفاصيل الحساب</h2>
        <div class="muted">تاريخ التوليد: {{ now()->format('Y-m-d') }}</div>
    </div>

    <hr class="hr"/>

    {{-- بطاقة: بيانات العملية --}}
    <div class="card">
        <div class="section">بيانات العملية</div>
        <table>
            <thead>
                <tr>
                    <th>رقم العملية</th>
                    <th>التاريخ</th>
                    <th>الموظف</th>
                    <th>المستفيد</th>
                    <th>العميل</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $sale->id }} / {{ $sale->agency_id }}</td>
                    <td>{{ $sale->sale_date ?? '-' }}</td>
                    <td>{{ $sale->user->name ?? (Auth::user()->name ?? '-') }}</td>
                    <td>{{ $sale->beneficiary_name ?? '-' }}</td>
                    <td>{{ $sale->customer->name ?? '-' }}</td>
                    <td>{{ $sale->status ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section">تفاصيل الخدمة</div>
        <table>
            <thead>
                <tr>
                    <th>نوع الخدمة</th>
                    <th>المزود</th>
                    <th>PNR</th>
                    <th>المرجع</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $sale->service?->label ?? '-' }}</td>
                    <td>{{ $sale->provider?->name ?? '-' }}</td>
                    <td>{{ $sale->pnr ?? '-' }}</td>
                    <td>{{ $sale->reference ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section">القيم المالية</div>
        <table class="totals" style="margin-top:10px;">
            <thead>
                <tr>
                    <th style="text-align:right;">سعر البيع (أصل)</th>
                    <th style="text-align:right;">سعر الشراء</th>
                    <th style="text-align:right;">العمولة/الربح</th>
                    <th style="text-align:right;">المدفوع حتى الآن</th>
                    <th style="text-align:right;">المتبقي على العميل</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="blue">{{ $money($usdSell) }}</td>
                    <td class="blue">{{ $money($usdBuy) }}</td>
                    <td class="{{ $grossProfit >= 0 ? 'green' : 'red' }}">{{ $money($grossProfit) }}</td>
                    <td class="green">{{ $money($amountPaid) }}</td>
                    <td>
                        @if($remaining > 0)
                            <span class="red">{{ $money($remaining) }}</span>
                        @else
                            <span class="muted">تم السداد بالكامل</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section">ملاحظات</div>
        <table>
            <thead>
                <tr><th>تفاصيل إضافية</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $sale->details ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <hr class="hr"/>

    <div class="footer-note">
        تم إنشاء هذا التقرير بواسطة نظام إدارة وكالات السفر – {{ $agency->name ?? '' }}<br>
        {{ Carbon::now()->translatedFormat('Y-m-d H:i') }}
    </div>

</body>
</html>
