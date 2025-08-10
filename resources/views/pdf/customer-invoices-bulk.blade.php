@php
    $dir      = 'rtl';
    $currency = $agency->currency ?? (Auth::user()->agency->currency ?? 'USD');
    $money    = fn($v) => number_format((float)$v, 2) . ' ' . $currency;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <title>كشف حساب مفصل – {{ $customer->name }}</title>
    <style>
        :root{
            /* نستخدم فقط هذه المتغيرات */
            --primary-100: {{ $colors['primary-100'] ?? '209,250,229' }}; /* rgb */
            --primary-500: {{ $colors['primary-500'] ?? '16,185,129'  }}; /* rgb */
            --primary-600: {{ $colors['primary-600'] ?? '5,150,105'   }}; /* rgb */

            --border: #e5e7eb;
            --muted: #6b7280;
            --heading: #111827; /* أسود داكن للعناوين */
        }

        @page { size: A4; margin: 14mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color:#111827; line-height: 1.55; }
        h1,h2,h3 { margin:0 0 10px; color: var(--heading); font-weight: 800; }
        .muted { color: var(--muted); font-size: 12px; }
        .hr { border:0; border-top: 2px solid var(--border); margin: 10px 0; }

        /* الهيدر: شعار + اسم الوكالة فقط */
        .invoice-head {
            text-align: center;
            padding: 10px 0 12px;
            border-bottom: 3px solid rgb(var(--primary-600));  /* لون الثيم */
            margin-bottom: 14px;
        }
        .brand { display:flex; align-items:center; justify-content:center; gap:12px; }
        .brand img { height: 48px; width:auto; }
        .brand .name { font-size: 20px; font-weight:800; color: #111827; } /* أسود */

        .title { text-align:center; margin-top: 6px; }
        .title h2 { margin:0; font-size: 20px; color: #111827; }          /* أسود */
        .title .muted { margin-top: 2px; }

        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th,td { border:1px solid var(--border); padding:6px 8px; font-size:12px; text-align:right; }
        th {
            background: rgba(var(--primary-100), .1); /* تظليل من الثيم */
            color: #111827;                              /* أسود */
            font-weight: 700;
        }

        .blue { color:#2563eb; }  /* للأرقام المعلوماتية فقط */
        .green{ color:#16a34a; }
        .red  { color:#dc2626; }

        .section {
            margin: 14px 0 8px;
            padding: 6px 10px;
            border-inline-start: 4px solid rgb(var(--primary-600)); /* خط ملون جانبي */
            background: rgba(var(--primary-100), .1);               /* شفافية أعلى */
            font-weight: 700;
            color: #111827;                                         /* أسود */
        }

        .beneficiary-title { font-weight:800; margin-bottom:6px; color: #111827; } /* أسود */

        .beneficiary-card {
            position: relative;
            border:1px solid var(--border);
            border-radius:8px;
            padding:10px;
            margin-bottom:12px;
            background: #fff;
        }
        /* شريط رفيع ملون على طرف البطاقة */
        .beneficiary-card:before{
            content:"";
            position:absolute;
            inset-inline-start:0; top:0; bottom:0;
            width:4px;
            background: rgb(var(--primary-600));
            border-radius:8px 0 0 8px;
        }

        .waterline { margin: 12px 0; border-top: 1px dashed var(--border); }

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

    <div class="title">
        <h2>كشف حساب مفصل للعميل: {{ $customer->name }}</h2>
        <div class="muted">تاريخ التوليد: {{ now()->format('Y-m-d') }}</div>
    </div>

    <hr class="hr"/>

    @foreach($reports as $i => $r)
        <div class="beneficiary-card">
            <div class="beneficiary-title">كشف سداد لـ {{ $r->beneficiary_name }}</div>

            <div class="section">تفاصيل العمليات للمستفيد</div>
            <table>
                <thead>
                    <tr>
                        <th>المرحلة</th>
                        <th>التاريخ</th>
                        <th>سعر الخدمة</th>
                        <th>المدفوع</th>
                        <th>الحالة</th>
                        <th>المرجع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($r->scenarios as $idx => $s)
                        <tr>
                            <td>{{ $idx === 0 ? 'الحجز الأول' : ($s['status'] ?? 'تعديل') }}</td>
                            <td>{{ $s['date'] ?? '-' }}</td>
                            <td class="blue">{{ $money($s['usd_sell']) }}</td>
                            <td class="green">{{ $money($s['amount_paid']) }}</td>
                            <td>{{ $s['status'] ?? '-' }}</td>
                            <td>{{ $s['note'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="section">سجل التحصيلات</div>
            <table>
                <thead>
                    <tr>
                        <th>المبلغ</th>
                        <th>التاريخ</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($r->collections as $c)
                        <tr>
                            <td class="green">{{ $money($c['amount']) }}</td>
                            <td>{{ $c['payment_date'] }}</td>
                            <td>{{ $c['note'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted" style="text-align:center;">لا توجد تحصيلات</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="totals" style="margin-top:10px;">
                <thead>
                <tr>
                    <th style="text-align:right;">سعر الخدمة (أصل)</th>
                    <th style="text-align:right;">الاستردادات</th>
                    <th style="text-align:right;">المحصل (إجمالي)</th>
                    <th style="text-align:right;">الصافي بعد الاسترداد</th>
                    <th style="text-align:right;">المتبقي / رصيد للعميل</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="blue">{{ $money($r->invoice_total_true) }}</td>
                    <td class="red">{{ $money($r->refund_total) }}</td>
                    <td class="green">{{ $money($r->total_collected) }}</td>
                <td class="blue">{{ $money($r->net_total) }}</td>

                <td>
                    @php
                        $remForCust = (float)($r->remaining_for_customer ?? 0); // متبقي على العميل
                        $remForComp = (float)($r->remaining_for_company ?? 0);  // رصيد للعميل
                    @endphp

                    @if ($remForCust > 0)
                        <span class="red">على العميل: {{ $money($remForCust) }}</span>
                    @elseif ($remForComp > 0)
                        <span class="green">رصيد للعميل: {{ $money($remForComp) }}</span>
                    @else
                        <span class="muted">تم السداد بالكامل</span>
                    @endif
                </td>

                </tr>
                </tbody>

            </table>
        </div>

        @if($i < $reports->count() - 1)
            <div class="waterline"></div>
        @endif
    @endforeach

    <hr class="hr"/>

    <div class="section">الملخص الإجمالي للمستفيدين المحددين</div>
    <table>
<thead>
  <tr>
    <th>عدد المستفيدين</th>
    <th>إجمالي أصل الفواتير</th>
    <th>إجمالي الاستردادات</th>
    <th>إجمالي الصافي بعد الاسترداد</th>
    <th>إجمالي المحصل</th>
    <th>إجمالي المتبقي على العملاء</th>
    <th>إجمالي الرصيد للعملاء</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>{{ $totals['count'] }}</td>
        <td class="blue">{{ $money($totals['invoice_total_true']) }}</td>
        <td class="red">{{ $money($totals['refund_total']) }}</td>
        <td class="blue">{{ $money($totals['net_total']) }}</td>
        <td class="green">{{ $money($totals['total_collected']) }}</td>
        <td class="red">{{ $money($totals['remaining_for_customer']) }}</td>
        <td class="green">{{ $money($totals['remaining_for_company']) }}</td>


  </tr>
</tbody>

    </table>

    <div class="footer-note">
        تم إنشاء هذا التقرير بواسطة نظام إدارة وكالات السفر – {{ $agency->name ?? '' }}
    </div>

</body>
</html>
