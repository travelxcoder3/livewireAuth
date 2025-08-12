@php
    use Illuminate\Support\Facades\Storage;

    $logoData = null; $mime = 'image/png';
    $stored = trim($agency->logo ?? '');
    if ($stored !== '' && Storage::disk('public')->exists($stored)) {
        $fullPath = Storage::disk('public')->path($stored);
        $logoData = base64_encode(file_get_contents($fullPath));
        $mime = mime_content_type($fullPath) ?: 'image/png';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <title>{{ $lang==='ar' ? 'عرض السعر' : 'Quotation' }}</title>
  <style>
    /* ===== صفحة الطباعة ===== */
    @page { size: A4; margin: 12mm; }
    * { box-sizing: border-box; }
    body{
      font-family: "DejaVu Sans", Segoe UI, Tahoma, sans-serif;
      font-size: 12px; color:#222; margin:0;
    }

    .wrap{ width:100%; }
    .header{ text-align:center; margin-bottom:12px; }
    .logo{ max-height:78px; object-fit:contain; }
    .title{ font-size:22px; margin:8px 0 2px; color:#2c3e50; }

    /* معلومات أعلى الصفحة */
    .info{ width:100%; border-collapse:collapse; margin:10px 0 14px; }
    .info td{ padding:6px 8px; border:1px solid #ddd; vertical-align:middle; }
    .info .to{ font-route:600; }

    /* جدول الخدمات */
    table.services{
      width:100%;
      border-collapse:collapse;
      table-layout:auto;        /* ← يمنع تكسير الحروف عموديًا */
      page-break-inside:auto;
    }
    .services thead { display: table-header-group; } /* ← يكرر الرأس في كل صفحة */
    .services th, .services td{
      border:1px solid #d0d7de;
      padding:8px 10px;
      vertical-align:top;
    }
    .services th{
      background:#eef1f5;
      font-route:700;
      text-align:{{ $lang==='ar' ? 'center' : 'left' }};
    }
    /* عرض أعمدة متوازن */
    .col-idx   { width:42px;  text-align:center; }
    .col-air   { width:140px; }
    .col-det   { width:auto;  }
    .col-wt    { width:90px;  text-align:center; }
    .col-cls   { width:90px;  text-align:center; }
    .col-terms { width:230px; }
    .col-price { width:130px; text-align:{{ $lang==='ar' ? 'left' : 'right' }}; }

    /* منع كسر الحروف عموديًا داخل الخلايا النصية */
    .services td{
      white-space: normal;      /* ← الطبيعي أفضل للطباعة */
      word-break: normal;
      overflow-wrap: anywhere;  /* سطر جديد عند اللزوم بدون تقطيع حرفي */
    }

    /* اتجاه مخصص داخل خلايا معينة (أكواد/أرقام) */
    .ltr { direction:ltr; unicode-bidi:bidi-override; }
    .rtl { direction:rtl; unicode-bidi:bidi-override; }

    /* الإجماليات والملاحظات */
    .totals{ margin-top:12px; font-size:13px; font-route:700; }
    .totals div{ margin:2px 0; }
    .notes{ margin-top:12px; }
    .notes strong{ display:block; margin-bottom:6px; }
    .notes ul{ margin:6px 0 0 18px; padding:0; }

    /* التوقيع */
    .signature{ margin-top:26px; font-route:700; text-align: {{ $lang==='ar' ? 'left' : 'right' }}; }

  /* أضف داخل <style> لديك إن لم تكن موجودة */
  .notes li, .notes div { white-space: pre-wrap; word-break: normal; overflow-wrap: anywhere; }

    /* تحسينات طباعة إضافية */
    @media print {
      a, button { display:none !important; }
    }
  </style>
</head>
<body>
<div class="wrap">

  <!-- الترويسة -->
  <div class="header">
    @if($logoData)
      <img class="logo" src="data:{{ $mime }};base64,{{ $logoData }}" alt="logo">
    @endif
    <div class="title">{{ $lang==='ar' ? 'عرض السعر' : 'Quotation' }}</div>
  </div>

  <!-- معلومات أعلى الصفحة -->
  <table class="info">
    <tr>
      <td>
        <strong>{{ $lang==='ar' ? 'التاريخ:' : 'Date:' }}</strong>
        <span class="ltr">{{ $quotationDate }}</span>
      </td>
      <td style="text-align:{{ $lang==='ar' ? 'left' : 'right' }};">
        <strong>{{ $lang==='ar' ? 'رقم عرض السعر:' : 'Quotation No:' }}</strong>
        <span class="ltr">{{ $quotationNumber }}</span>
      </td>
    </tr>
    <tr>
      <td colspan="2" class="to">
        <strong>{{ $lang==='ar' ? 'إلى:' : 'To:' }}</strong>
        {{ $toClient }}
      </td>
    </tr>
  </table>

  <!-- جدول الخدمات -->
  <table class="services">
    <thead>
      <tr>
        <th class="col-idx">#</th>
        <th class="col-air">{{ $lang==='ar' ? 'الخدمة' : 'Service' }}</th>
        <th class="col-wt">{{ $lang==='ar' ? 'المسار' : 'Route' }}</th>
        <th class="col-cls">{{ $lang==='ar' ? 'التاريخ' : 'Date' }}</th>
        <th class="col-det">{{ $lang==='ar' ? 'الوصف' : 'Description' }}</th>
        <th class="col-terms">{{ $lang==='ar' ? 'الشروط والأحكام' : 'Terms & Conditions' }}</th>
        <th class="col-det">{{ $lang==='ar' ? 'السعر' : 'Description' }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse($services as $i => $s)
        <tr>
          <td class="col-idx">{{ $i+1 }}</td>
        <td class="col-air">{{ $s['service_name'] ?? '' }}</td>      
        <td class="col-wt">{{ $s['route'] ?? '' }}</td>       
        <td class="col-cls ltr">{{ $s['service_date'] ?? '' }}</td>     
        <td class="col-det">{{ $s['description'] ?? '' }}</td>     
        <td class="col-terms">{{ $s['conditions'] ?? '' }}</td>
        <td class="col-price">
            <span class="ltr">{{ number_format((float)($s['price'] ?? 0), 2) }}</span>
            {{ $currency }}
        </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center;color:#777;">
            {{ $lang==='ar' ? 'لا توجد خدمات' : 'No services' }}
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <!-- الإجماليات -->
  <div class="totals">
    <div>{{ $lang==='ar' ? 'الإجمالي الفرعي:' : 'Subtotal:' }}
      <span class="ltr">{{ number_format($total, 2) }}</span> {{ $currency }}
    </div>
    <div>{{ $taxName }} ({{ $taxRate }}%):
      <span class="ltr">{{ number_format($taxAmount, 2) }}</span> {{ $currency }}
    </div>
    <div>{{ $lang==='ar' ? 'الإجمالي الكلي:' : 'Grand Total:' }}
      <span class="ltr">{{ number_format($grandTotal, 2) }}</span> {{ $currency }}
    </div>
  </div>

  <!-- الشروط والملاحظات -->


    @if($terms && count($terms))
      <div class="notes">
        <strong>{{ $lang==='ar' ? 'الشروط:' : 'Terms:' }}</strong>
        <ul>
          @foreach($terms as $t)
            <li>{{ $t }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if(strlen(trim($notes ?? '')) > 0)
      <div class="notes">
        <strong>{{ $lang==='ar' ? 'ملاحظات:' : 'Notes:' }}</strong>
        <div>{!! nl2br(e($notes)) !!}</div>
      </div>
    @endif


    <div class="signature">
      {{ $lang==='ar' ? 'مع تحيات إدارة المبيعات' : 'Sales Department' }}
    </div>

</div>
</body>
</html>
