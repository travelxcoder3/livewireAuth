@php
use App\Services\ThemeService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/** الوكالة + الثيم */
$agency    = $customer->agency ?? Auth::user()?->agency;
$themeName = strtolower($agency->theme_color ?? 'emerald');
$colors    = ThemeService::getCurrentThemeColors($themeName) ?? [
    'primary-100' => '209,250,229',
    'primary-500' => '16,185,129',
    'primary-600' => '5,150,105',
];

/** الشعار → data URL */
$logoDataUrl = null;
if ($agency && !empty($agency->logo)) {
    $logoPath = storage_path('app/public/' . ltrim($agency->logo, '/'));
    if (file_exists($logoPath)) {
        $mime = mime_content_type($logoPath) ?: 'image/png';
        $logoDataUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }
}

/** العملة (fallback) */
$currency = $currency ?? ($agency->currency ?? 'USD');
@endphp

<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>التقرير المالي لمشتريات العميل</title>
<style>
  :root{
    --pri-100: {{ $colors['primary-100'] ?? '209,250,229' }};
    --pri-500: {{ $colors['primary-500'] ?? '16,185,129'  }};
    --pri-600: {{ $colors['primary-600'] ?? '5,150,105'   }};
    --border:#e5e7eb; --text:#111827; --muted:#6b7280;
  }
  @page { size: A4; margin: 14mm 10mm; }
  *{box-sizing:border-box}
  body{ font-family: DejaVu Sans, Tahoma, Arial, sans-serif; color:var(--text); line-height:1.55; background:#fff; }
  h1,h2,h3{ margin:0 0 10px; font-weight:800; color:#111827; }
  .muted{ color:var(--muted); font-size:12px; }
  .card{ background:#fff; border:1px solid var(--border); border-radius:12px; padding:12px; margin-bottom:12px; position:relative; }
  .card:before{ content:""; position:absolute; inset-inline-start:0; top:0; bottom:0; width:4px; background:rgb(var(--pri-600)); border-radius:12px 0 0 12px; }
  .grid3{ display:grid; grid-template-columns: repeat(3,1fr); gap:8px; }
  table{ width:100%; border-collapse:collapse; }
  th,td{ border:1px solid var(--border); padding:6px 8px; font-size:12px; text-align:center; }
  thead th{ background:rgb(var(--pri-600)); color:#fff; }
  .toolbar{ display:flex; justify-content:space-between; align-items:center; padding-bottom:8px; border-bottom:3px solid rgb(var(--pri-600)); margin-bottom:14px; }
  .title{ font-size:20px; font-weight:800; }
  .brand{ display:flex; align-items:center; gap:12px; }
  .brand img{ height:42px; width:auto; }
  .brand .name{ font-size:16px; font-weight:800; color:#111827; }
  .pill{ display:inline-block; padding:2px 8px; border-radius:999px; font-weight:700; font-size:12px; border:1px solid #e5e7eb; }
  .pill-green{ background:rgba(16,185,129,.10); color:#065f46; border-color:rgba(16,185,129,.25); }
  .pill-amber{ background:rgba(245,158,11,.12); color:#92400e; border-color:rgba(245,158,11,.25); }
  .pill-gray{ background:#f3f4f6; color:#374151; }
  .badge-ok{ color:#065f46; font-weight:700; }
  .badge-debt{ color:#b91c1c; font-weight:700; }
  .footer{ margin-top:8px; font-size:11px; color:var(--muted); text-align:center; }
</style>
</head>
<body>

  <!-- رأس التقرير: الشعار + اسم الشركة + التاريخ -->
  <div class="toolbar">
    <div class="brand">
      @if($logoDataUrl)<img src="{{ $logoDataUrl }}" alt="Logo">@endif
      <div class="name">{{ $agency->name ?? 'الشركة' }}</div>
    </div>
    <div class="title">التقرير المالي لمشتريات العميل</div>
    <div class="muted">{{ now()->format('Y-m-d H:i') }}</div>
  </div>

  <!-- بيانات العميل -->
  <div class="card">
    <h3>بيانات العميل</h3>
    <div class="grid3">
      <div><span class="muted">اسم العميل:</span> <strong>{{ $customer->name }}</strong></div>
      <div><span class="muted">نوع الحساب:</span> {{ ['individual'=>'فرد','company'=>'شركة','organization'=>'منظمة'][$customer->account_type] ?? 'غير محدد' }}</div>
      <div><span class="muted">رقم الحساب:</span> {{ $accountNumber }}</div>
      <div><span class="muted">تاريخ فتح الحساب:</span> {{ $customer->created_at->format('Y-m-d') }}</div>
      <div><span class="muted">الجوال:</span> {{ $customer->phone ?? '—' }}</div>
      <div><span class="muted">البريد الإلكتروني:</span> {{ $customer->email ?? '—' }}</div>
      <div><span class="muted">العملة:</span> {{ $currency }}</div>
    </div>
  </div>

  <!-- العمليات -->
  <div class="card">
    <h3>عمليات البيع والتحصيل</h3>
    <table>
      <thead>
        <tr>
          <th>تاريخ العملية</th>
          <th>نوع العملية</th>
          <th>حالة الدفع</th>
          <th>مبلغ العملية</th>
          <th>المرجع</th>
          <th>وصف الحالة</th>
        </tr>
      </thead>
      <tbody>
      @forelse($sortedTransactions as $row)
        @php
          $amt = ($row['debit'] ?? 0) > 0 ? $row['debit'] : ($row['credit'] ?? 0);
          $isCollect = isset($row['desc']) && str_starts_with($row['desc'],'سداد');
          $pill =
            ($row['status'] === 'سداد كلي' || $row['status'] === 'تم السداد') ? 'pill-green' :
            ($row['status'] === 'سداد جزئي' ? 'pill-amber' : 'pill-gray');
        @endphp
        <tr>
          <td>{{ \Carbon\Carbon::parse($row['date'])->format('Y-m-d') }}</td>
          <td>{{ $isCollect ? 'تحصيل' : 'بيع/حدث عملية' }}</td>
          <td><span class="pill {{ $pill }}">{{ $row['status'] }}</span></td>
          <td>{{ number_format($amt,2) }} {{ $currency }}</td>
          <td>—</td>
          <td>{{ $row['desc'] }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted">لا توجد عمليات</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <!-- الملخص -->
  <div class="card">
    <h3>ملخص الحساب</h3>
    <div class="grid3">
      <div>
        <strong>إجمالي المبيعات:</strong>
        <div>{{ number_format($activeSales,2) }} {{ $currency }}</div>
        <div class="muted">(جميع مبالغ عمليات البيع)</div>
      </div>
      <div>
        <strong>إجمالي التحصيل:</strong>
        <div>{{ number_format($netPayments,2) }} {{ $currency }}</div>
        <div class="muted">(المبالغ التي دفعها العميل فعلياً)</div>
      </div>
      <div>
        <strong>الرصيد الفارق:</strong>
        <div>
          @if($netBalance>0)
            <span class="badge-debt">مدين: {{ number_format($netBalance,2) }} {{ $currency }}</span>
          @elseif($netBalance==0)
            <span class="badge-ok">تم السداد بالكامل</span>
          @else
            لا يوجد رصيد مستحق
          @endif
        </div>
        <div class="muted">(إجمالي المبيعات - إجمالي التحصيل)</div>
      </div>
      <div>
        <strong>رصيد العميل لدى الشركة:</strong>
        <div>{{ number_format($availableBalanceToPayOthers,2) }} {{ $currency }}</div>
        <div class="muted">(رصيد العميل بناءً على الاستردادات)</div>
      </div>
    </div>
  </div>

  <div class="footer">
    {{ $agency->name ?? 'الشركة' }} · TRAVEL-X
  </div>
</body>
</html>
