<table>
    <thead>
        <tr>
            <th colspan="8">تقرير مبيعات العملاء</th>
        </tr>
    </thead>
</table>

@if(!$customerId)
    <table>
        <thead>
            <tr>
                <th>العميل</th>
                <th>عدد</th>
                <th>البيع</th>
                <th>الشراء</th>
                <th>الربح</th>
                <th>المستحق</th>
                <th>العملة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($perCustomer as $row)
                <tr>
                    <td>{{ $row['customer']?->name }}</td>
                    <td>{{ $row['count'] }}</td>
                    <td>{{ number_format($row['sell'],2) }}</td>
                    <td>{{ number_format($row['buy'],2) }}</td>
                    <td>{{ number_format($row['profit'],2) }}</td>
                    <td>{{ number_format($row['remaining'],2) }}</td>
                    <td>{{ $currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <table>
        <thead>
            <tr>
                <th>الخدمة</th>
                <th>عدد</th>
                <th>البيع</th>
                <th>الشراء</th>
                <th>الربح</th>
                <th>المستحق</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byService as $sid => $row)
                <tr>
                    <td>{{ $row['firstRow']?->service?->label }}</td>
                    <td>{{ $row['count'] }}</td>
                    <td>{{ number_format($row['sell'],2) }}</td>
                    <td>{{ number_format($row['buy'],2) }}</td>
                    <td>{{ number_format($row['profit'],2) }}</td>
                    <td>{{ number_format($row['remaining'],2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>الشهر</th>
                <th>عدد</th>
                <th>البيع</th>
                <th>الشراء</th>
                <th>الربح</th>
                <th>المستحق</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byMonth as $ym => $row)
                <tr>
                    <td>{{ $ym }}</td>
                    <td>{{ $row['count'] }}</td>
                    <td>{{ number_format($row['sell'],2) }}</td>
                    <td>{{ number_format($row['buy'],2) }}</td>
                    <td>{{ number_format($row['profit'],2) }}</td>
                    <td>{{ number_format($row['remaining'],2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th colspan="9">العمليات</th>
            </tr>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>المستفيد</th>
                <th>PNR</th>
                <th>المرجع</th>
                <th>الخدمة</th>
                <th>البيع</th>
                <th>الشراء</th>
                <th>المستحق</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $s)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $s->sale_date }}</td>
                    <td>{{ $s->beneficiary_name }}</td>
                    <td>{{ $s->pnr }}</td>
                    <td>{{ $s->reference }}</td>
                    <td>{{ $s->service?->label }}</td>
                    <td>{{ number_format($s->usd_sell,2) }}</td>
                    <td>{{ number_format($s->usd_buy,2) }}</td>
                    <td>{{ number_format($s->remaining_payment ?? 0,2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
