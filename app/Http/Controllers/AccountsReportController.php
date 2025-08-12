<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;
use App\Tables\AccountTable;

class AccountsReportController extends Controller
{
    /**
     * تحميل تقرير الحسابات بصيغة PDF مع تطبيق الفلاتر الحالية.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf(Request $request)
    {
        $agency = auth()->user()->agency;
        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $user = Auth::user();
        $isAdmin = $user->hasRole('agency-admin');

$query = Sale::with([
    'user:id,name',
    'provider:id,name',
    'service:id,label',      
    'customer:id,name',
    'agency:id,name',
    'duplicatedBy:id,name',
])
    ->whereIn('agency_id',$agencyIds)
    ->when(!$isAdmin, fn($q)=>$q->where('user_id',$user->id))
    ->when($request->serviceTypeFilter, fn($q)=>$q->where('service_type_id',$request->serviceTypeFilter))
    ->when($request->providerFilter, fn($q)=>$q->where('provider_id',$request->providerFilter))
    ->when($request->accountFilter, fn($q)=>$q->where('customer_id',$request->accountFilter))
    ->when($request->startDate, fn($q)=>$q->whereDate('sale_date','>=',$request->startDate))
    ->when($request->endDate,   fn($q)=>$q->whereDate('sale_date','<=',$request->endDate))
    ->orderBy($request->sortField ?? 'created_at', $request->sortDirection ?? 'desc');

$sales = $query->get();
$totalSales = $sales->sum('usd_sell');

// استخراج ترتيب الأعمدة وعناوينها من جدول الواجهة
$columns = array_values(
    array_filter(AccountTable::columns(), fn($c) => ($c['key'] ?? '') !== 'actions')
);

$fields  = array_map(fn($c) => $c['key'], $columns);
$headers = [];
$formats = [];
foreach ($columns as $c) {
    $headers[$c['key']] = $c['label'] ?? $c['key'];
    if (isset($c['format'])) $formats[$c['key']] = $c['format'];
}


// استخرج الأعمدة بنفس ترتيب جدول العرض واستبعد actions
$columns = array_values(
    array_filter(AccountTable::columns(), fn($c) => ($c['key'] ?? '') !== 'actions')
);
$fields  = array_map(fn($c) => $c['key'], $columns);
$headers = [];
$formats = [];
foreach ($columns as $c) {
    $headers[$c['key']] = $c['label'] ?? $c['key'];
    if (isset($c['format'])) $formats[$c['key']] = $c['format'];
}

$html = view('reports.accounts-full-pdf', [
    'sales'      => $sales,
    'totalSales' => $totalSales,
    'agency'     => $agency,
    'startDate'  => $request->startDate,
    'endDate'    => $request->endDate,
    'fields'     => $fields,
    'headers'    => $headers,
    'formats'    => $formats, // ⬅️ ضروري
])->render();


        // أنشئ PDF وردّه
        return response(
            Browsershot::html($html)
                ->format('A4')
                ->noSandbox()
                ->emulateMedia('screen')
                ->waitUntilNetworkIdle()
                ->pdf()
        )
            ->withHeaders([
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="accounts-full-report.pdf"',
            ]);
    }

    protected function prepareReportData()
    {
        $agency = auth()->user()->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $sales = Sale::with(['service', 'provider', 'account', 'customer'])
            ->whereIn('agency_id', $agencyIds)
            ->when(request('search'), function ($query) {
                $term = '%' . request('search') . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('beneficiary_name', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('pnr', 'like', $term);
                });
            })
            ->when(request('serviceTypeFilter'), fn($q) => $q->where('service_type_id', request('serviceTypeFilter')))
            ->when(request('providerFilter'), fn($q) => $q->where('provider_id', request('providerFilter')))
            ->when(request('accountFilter'), fn($q) => $q->where('account_id', request('accountFilter')))
            ->when(request('pnrFilter'), fn($q) => $q->where('pnr', 'like', '%' . request('pnrFilter') . '%'))
            ->when(request('referenceFilter'), fn($q) => $q->where('reference', 'like', '%' . request('referenceFilter') . '%'))
            ->when(request('startDate'), fn($q) => $q->whereDate('created_at', '>=', request('startDate')))
            ->when(request('endDate'), fn($q) => $q->whereDate('created_at', '<=', request('endDate')))
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'agency' => $agency,
            'sales' => $sales,
            'startDate' => request('startDate'),
            'endDate' => request('endDate'),
            'totalSales' => $sales->sum('usd_sell'),
        ];
    }
}
