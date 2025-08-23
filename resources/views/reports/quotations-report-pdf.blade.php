@php
    use Illuminate\Support\Facades\Auth;
    use Carbon\Carbon;

    $agency = Auth::user()->agency;
    $logoPath = null;
    if ($agency && !empty($agency->logo)) {
        $logoPath = storage_path('app/public/' . ltrim($agency->logo, '/'));
        if (!file_exists($logoPath)) $logoPath = null;
    }
    $logoData = null; $mime = 'image/png';
    if ($logoPath) {
        try { $logoData = base64_encode(file_get_contents($logoPath)); $mime = mime_content_type($logoPath) ?: 'image/png'; }
        catch (\Throwable $e) { $logoData = null; }
    }
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تقرير عروض الأسعار</title>
<style>
    body{font-family:'DejaVu Sans',sans-serif;direction:rtl;font-size:12px;margin:20px}
    .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
    .logo{max-height:80px;max-width:150px;object-fit:contain}
    .title{font-size:20px;font-weight:bold;text-align:center;flex-grow:1}
    table{width:100%;border-collapse:collapse;margin-bottom:15px}
    th,td{border:1px solid #aaa;padding:6px;text-align:center}
    th{background:#eee}
    .footer{text-align:center;font-size:10px;color:#666;margin-top:10px}
</style>
</head>
<body>
    <div class="header">
        <div style="flex:1;">
            @if ($logoData)
                <img src="data:{{ $mime }};base64,{{ $logoData }}" class="logo" />
            @endif
        </div>
        <div class="title">تقرير عروض الأسعار</div>
        <div style="flex:1;"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>رقم العرض</th>
                <th>التاريخ</th>
                <th>العميل</th>
                <th>العملة</th>
                <th>نسبة الضريبة%</th>
                <th>الإجمالي الفرعي</th>
                <th>الضريبة</th>
                <th>الإجمالي الكلي</th>
                <th>المستخدم</th>
                <th>عدد البنود</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->quotation_number }}</td>
                    <td>{{ $r->quotation_date ? \Carbon\Carbon::parse($r->quotation_date)->format('Y-m-d') : '-' }}</td>
                    <td>{{ $r->to_client ?? '-' }}</td>
                    <td>{{ $r->currency ?? '-' }}</td>
                    <td>{{ rtrim(rtrim(number_format((float)$r->tax_rate,2), '0'),'.') }}</td>
                    <td>{{ number_format((float)$r->subtotal, 2) }}</td>
                    <td>{{ number_format((float)$r->tax_amount, 2) }}</td>
                    <td>{{ number_format((float)$r->grand_total, 2) }}</td>
                    <td>{{ $r->user?->name ?? '-' }}</td>
                    <td>{{ (int)($r->items_count ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>إجمالي السجلات: {{ $rows->count() }}</p>
        <p>تاريخ الإنشاء: {{ Carbon::now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
