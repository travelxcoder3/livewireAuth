@php
    use Carbon\Carbon;

    $logoPath = storage_path('app/public/' . ($customer->agency->logo ?? ''));
    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    $mime = file_exists($logoPath) ? mime_content_type($logoPath) : 'image/png';

    $transactions = [];

    foreach ($sales as $sale) {
        $transactions[] = [
            'date' => $sale->sale_date,
            'type' => 'فاتورة مبيعات',
            'ref' => $sale->receipt_number ?? $sale->id,
            'description' => 'بيع: ' . ($sale->note ?? '---'),
            'debit' => $sale->usd_sell,
            'credit' => 0,
        ];
    }

    foreach ($collections as $collection) {
        $transactions[] = [
            'date' => $collection->payment_date,
            'type' => 'سند قبض',
            'ref' => $collection->receipt_number ?? $collection->id,
            'description' => 'تحصيل: ' . ($collection->note ?? '---'),
            'debit' => 0,
            'credit' => $collection->amount,
        ];
    }
    function numberToWords($number)
    {
        if (!class_exists('NumberFormatter')) {
            return number_format($number, 2) . ' دولار أمريكي';
        }

        $f = new NumberFormatter('ar', NumberFormatter::SPELLOUT);
        return $f->format($number);
    }

    usort($transactions, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));

    $totalDebit = collect($transactions)->sum('debit');
    $totalCredit = collect($transactions)->sum('credit');
    $balance = $totalDebit - $totalCredit;
    $totalMovement = $totalDebit + $totalCredit;
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>كشف حساب عميل</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
            font-size: 13px;
            margin: 30px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            width: 140px;
            height: 80px;
            object-fit: contain;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            flex-grow: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            border: 1px solid #aaa;
            padding: 6px;
            text-align: center;
        }

        table th {
            background-color: #eee;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }

        .summary {
            margin-top: 20px;
            font-size: 14px;
        }

        .summary div {
            margin-bottom: 5px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header" style="position: relative;">
        @if ($logoData)
            <img src="data:{{ $mime }};base64,{{ $logoData }}" class="logo">
        @else
            <div class="logo"></div>
        @endif
        <div class="title"
            style="
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        white-space: nowrap;
    ">
            كشف حساب العميل
        </div>
    </div>


    <!-- Client Info -->
    <div class="section-title">معلومات العميل</div>
    <table>
        <tr>
            <th>اسم العميل</th>
            <td>{{ $customer->name }}</td>
            <th>رقم الحساب</th>
            <td>{{ $customer->id }}</td>
            <th>نوع الحساب</th>
            <td>{{ $customer->has_commission ? 'عمولة' : 'عادي' }}</td>
        </tr>
        <tr>
            <th>البريد الإلكتروني</th>
            <td>{{ $customer->email ?? '—' }}</td>
            <th>الجوال</th>
            <td>{{ $customer->phone ?? '—' }}</td>
            <th>العملة</th>
            <td>USD</td>
        </tr>
        <tr>
            <th>العنوان</th>
            <td colspan="5">{{ $customer->address ?? '—' }}</td>
        </tr>
    </table>

    <!-- Transactions Table -->
    <div class="section-title">سجل العمليات</div>
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>نوع المستند</th>
                <th>رقم المستند</th>
                <th>البيان</th>
                <th>مدين (USD)</th>
                <th>دائن (USD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ $row['ref'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ number_format($row['debit'], 2) }}</td>
                    <td>{{ number_format($row['credit'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">لا توجد بيانات</td>
                </tr>
            @endforelse

            <!-- Totals -->
            <tr>
                <td colspan="4"><strong>الإجماليات</strong></td>
                <td><strong>{{ number_format($totalDebit, 2) }}</strong></td>
                <td><strong>{{ number_format($totalCredit, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4"><strong>إجمالي الحركة</strong></td>
                <td colspan="2"><strong>{{ number_format($totalMovement, 2) }} USD</strong></td>
            </tr>
            <tr>
                <td colspan="4"><strong>الرصيد النهائي</strong></td>
                <td colspan="2">
                    <strong style="color: {{ $balance > 0 ? 'red' : ($balance < 0 ? 'green' : 'gray') }}">
                        {{ number_format(abs($balance), 2) }}
                        {{ $balance > 0 ? 'على العميل' : ($balance < 0 ? 'للعميل' : '') }}
                    </strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary">
        <div style="margin-top: 10px; font-size: 12px;">
            الرصيد النهائي: {{ numberToWords(abs($balance)) }} 
            {{ $balance > 0 ? 'ديون فقط، قابلة للدفع نقداً أو تحويل بنكي' : 'رصيد دائن للعميل' }}
        </div>
    </div>

    <div class="footer">
        تم توليد التقرير بتاريخ {{ now()->format('Y-m-d H:i') }}
    </div>

</body>

</html>
