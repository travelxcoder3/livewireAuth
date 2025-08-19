@php
use App\Services\ThemeService;
use Illuminate\Support\Facades\Auth;

/** الوكالة + الثيم */
$agency    = $provider->agency ?? Auth::user()?->agency;
$themeName = strtolower($agency->theme_color ?? 'emerald');
$colors    = ThemeService::getCurrentThemeColors($themeName) ?? [
  'primary-100'=>'209,250,229','primary-500'=>'16,185,129','primary-600'=>'5,150,105'
];

/** الشعار → data URL */
$logoDataUrl = null;
if ($agency && !empty($agency->logo)) {
  $logoPath = storage_path('app/public/'.ltrim($agency->logo,'/'));
  if (file_exists($logoPath)) {
    $mime = mime_content_type($logoPath) ?: 'image/png';
    $logoDataUrl = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
  }
}

/** العملة */
$currency = $currency ?? ($agency->currency ?? 'USD');

/** إجمالي الغرامات (اختياري) */
$tot_penalty = $tot_penalty ?? 0;
@endphp

<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>التقرير المالي لمشتريات المزوّد</title>
<style>
  :root{
    --pri-100: {{ $colors['primary-100'] ?? '209,250,229' }};
    --pri-500: {{ $colors['primary-500'] ?? '16,185,129'  }};
    --pri-600: {{ $colors['primary-600'] ?? '5,150,105'   }};
    --border:#e5e7eb; --text:#111827; --muted:#6b7280;
  }
  @page { size:A4; margin:14mm 10mm }
  *{box-sizing:border-box}
  body{font-family:DejaVu Sans,Tahoma,Arial,sans-serif;color:var(--text);line-height:1.55}
  h1,h2,h3{margin:0 0 10px;font-weight:800}
  .muted{color:var(--muted);font-size:12px}
  .toolbar{display:flex;justify-content:space-between;align-items:center;padding-bottom:8px;border-bottom:3px solid rgb(var(--pri-600));margin-bottom:14px}
  .brand{display:flex;align-items:center;gap:12px}.brand img{height:42px}
  .title{font-size:20px;font-weight:800}
  .card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:12px;position:relative}
  .card:before{content:"";position:absolute;inset-inline-start:0;top:0;bottom:0;width:4px;background:rgb(var(--pri-600));border-radius:12px 0 0 12px}
  .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
  table{width:100%;border-collapse:collapse}
  th,td{border:1px solid var(--border);padding:6px 8px;font-size:12px;text-align:center}
  thead th{background:rgb(var(--pri-600));color:#fff}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-weight:700;font-size:12px;border:1px solid #e5e7eb}
  .pill-amber{background:rgba(245,158,11,.12);color:#92400e;border-color:rgba(245,158,11,.25)}
  .badge-ok{color:#065f46;font-weight:700}
  .badge-debt{color:#b91c1c;font-weight:700}
  .note{font-size:11px;color:#92400e}
  .footer{margin-top:8px;font-size:11px;color:var(--muted);text-align:center}
</style>
</head>
<body>

  <!-- رأس التقرير -->
  <div class="toolbar">
    <div class="brand">
      @if($logoDataUrl)<img src="{{ $logoDataUrl }}" alt="Logo">@endif
      <div class="name">{{ $agency->name ?? 'الشركة' }}</div>
    </div>
    <div class="title">التقرير المالي لمشتريات المزوّد</div>
    <div class="muted">{{ now()->format('Y-m-d H:i') }}</div>
  </div>

  <!-- بيانات المزوّد -->
  <div class="card">
    <h3>بيانات المزوّد</h3>
    <div class="grid3">
      <div><span class="muted">اسم المزوّد:</span> <strong>{{ $provider->name }}</strong></div>
      <div><span class="muted">رقم الحساب:</span> {{ $provider->id }}</div>
      <div><span class="muted">العملة:</span> {{ $currency }}</div>
    </div>
  </div>

  <!-- العمليات -->
  <div class="card">
    <h3>عمليات الشراء والسداد</h3>
    <table>
      <thead>
        <tr>
          <th>تاريخ العملية</th>
          <th>نوع العملية</th>
          <th>الحالة</th>
          <th>المبلغ</th>
          <th>المرجع</th>
          <th>الوصف</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          @php
            $isPenalty = ($r['drcr'] ?? '') === 'memo';
            $pill = $isPenalty ? 'pill-amber' : '';
          @endphp
          <tr>
            <td>{{ \Carbon\Carbon::parse($r['date'])->format('Y-m-d') }}</td>
            <td>
              {{ $r['type'] }}
              @if($isPenalty)
                <span class="pill {{ $pill }}">غرامة (لا تُحسب)</span>
              @endif
            </td>
            <td>{{ $r['status'] }}</td>
            <td>{{ number_format($r['amount'],2) }} {{ $currency }}</td>
            <td>{{ $r['ref'] ?? '—' }}</td>
            <td>{{ $r['desc'] }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="muted">لا توجد عمليات</td></tr>
        @endforelse
      </tbody>
    </table>
    @if(($tot_penalty ?? 0) > 0)
      <div class="note" style="margin-top:6px">
        * الغرامات للعرض فقط ولا تدخل في احتساب الرصيد.
      </div>
    @endif
  </div>

  <!-- الملخص -->
  <div class="card">
    <h3>ملخص الحساب</h3>
    <div class="grid3">
      <div>
        <strong>إجمالي الشراء:</strong>
        <div>{{ number_format($tot_buy,2) }} {{ $currency }}</div>
      </div>
      <div>
        <strong>إجمالي الدائن للمزوّد:</strong>
        <div>{{ number_format($tot_credit,2) }} {{ $currency }}</div>
        <div class="muted">(استرداد + إلغاء + مدفوع للمزوّد)</div>
      </div>
      <div>
        <strong>الرصيد:</strong>
        <div>
          @if($balance>0)
            <span class="badge-ok">له: {{ number_format($balance,2) }} {{ $currency }}</span>
          @elseif($balance<0)
            <span class="badge-debt">عليه: {{ number_format(abs($balance),2) }} {{ $currency }}</span>
          @else
            لا رصيد
          @endif
        </div>
      </div>
      <div>
        <strong>إجمالي الغرامات/الخصومات:</strong>
        <div>{{ number_format($tot_penalty,2) }} {{ $currency }}</div>
        <div class="muted">للعرض فقط</div>
      </div>
    </div>
  </div>

  <div class="footer">
    {{ $agency->name ?? 'الشركة' }} · TRAVEL-X
  </div>
</body>
</html>
