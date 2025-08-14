<?php

namespace App\Http\Controllers;

use App\Exports\SalesReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\DynamicListItem;
use Illuminate\Support\Facades\Auth;
use App\Tables\SalesTable; 

class SalesReportController extends Controller
{
    /**
     * تحميل تقرير المبيعات بصيغة PDF.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
public function downloadPdf(Request $request)
{
    $user   = Auth::user();
    $agency = $user->agency;

    $agencyIds = $agency->parent_id
        ? [$agency->id]
        : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

    $query = Sale::query()
        ->with([
            'user:id,name','provider:id,name','service:id,label','customer:id,name',
            'agency:id,name,currency',
        ])
        ->whereIn('agency_id', $agencyIds);


    // فلترة باسم الموظف مثل تقرير الحسابات
    $query->when($request->search, function($q) use ($request){
        $t = '%'.$request->search.'%';
        $q->whereHas('user', fn($u)=>$u->where('name','like',$t));
    });

    $query
        ->when($request->serviceTypeFilter, fn($q)=>$q->where('service_type_id',$request->serviceTypeFilter))
        ->when($request->providerFilter,    fn($q)=>$q->where('provider_id',$request->providerFilter))
        ->when($request->accountFilter,     fn($q)=>$q->where('customer_id',$request->accountFilter))
        ->when($request->pnrFilter, function($q) use ($request){
            $t = trim($request->pnrFilter);
            $q->where('pnr','like', mb_strlen($t)>=3 ? "%$t%" : "$t%");
        })
        ->when($request->referenceFilter, function($q) use ($request){
            $t = trim($request->referenceFilter);
            $q->where('reference','like', mb_strlen($t)>=3 ? "%$t%" : "$t%");
        })
        // التاريخ على sale_date مع whereBetween للاستفادة من الفهرس
        ->when($request->startDate && $request->endDate,
            fn($q)=>$q->whereBetween('sale_date', [$request->startDate, $request->endDate]))
        ->when($request->startDate && !$request->endDate,
            fn($q)=>$q->where('sale_date','>=',$request->startDate))
        ->when(!$request->startDate && $request->endDate,
            fn($q)=>$q->where('sale_date','<=',$request->endDate))
        ->orderBy($request->sortField ?? 'created_at', $request->sortDirection ?? 'desc');

    $sales      = $query->get();
    $totalSales = $sales->sum('usd_sell');

    // استخراج أعمدة PDF من جدول واجهة المبيعات (بدون عمود الإجراءات)
$columns = array_values(array_filter(SalesTable::columns(true, true), function($c){
    $k = $c['key'] ?? '';
    return !in_array($k, ['actions','duplicatedBy.name']); // إخفاء "تم التكرار بواسطة"
}));


    $fields  = array_map(fn($c)=>$c['key'], $columns);
    $headers = [];
    $formats = [];
    foreach ($columns as $c) {
        $headers[$c['key']] = $c['label'] ?? $c['key'];
        if (isset($c['format'])) $formats[$c['key']] = $c['format'];
    }

    $html = view('reports.sales-full-pdf', [
        'sales'      => $sales,
        'totalSales' => $totalSales,
        'agency'     => $agency,
        'startDate'  => $request->startDate,
        'endDate'    => $request->endDate,
        // ⬅️ هذه هي المتغيرات المفقودة التي سببت الخطأ
        'fields'     => $fields,
        'headers'    => $headers,
        'formats'    => $formats,
    ])->render();

    return response(
        Browsershot::html($html)
            ->format('A4')
            ->emulateMedia('screen')
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->pdf()
    )->withHeaders([
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="sales-report.pdf"',
    ]);
}

    /**
     * تحميل تقرير المبيعات بصيغة Excel.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Maatwebsite\Excel\Excel
     */
    public function downloadExcel(Request $request)
    {
        $data = $this->prepareReportData($request);

$columns = array_values(array_filter(
    \App\Tables\SalesTable::columns(true, true),
    fn($c)=>!in_array($c['key'] ?? '', ['actions','duplicatedBy.name'])
));
$fields  = array_map(fn($c)=>$c['key'], $columns);
$headers = []; $formats = [];
foreach ($columns as $c) {
    $headers[$c['key']] = $c['label'] ?? $c['key'];
    if (isset($c['format'])) $formats[$c['key']] = $c['format'];
}

return Excel::download(
    new \App\Exports\SalesReportExport(
        sales:    $data['sales'],
        fields:   $fields,
        headers:  $headers,
        formats:  $formats,
        currency: $currency,
    ),
    'sales-' . now()->format('Y-m-d') . '.xlsx'
);

    }

    /**
     * إعداد بيانات التقرير
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function prepareReportData(Request $request)
    {
        $agency = Auth::user()->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $query = Sale::with(['service', 'provider', 'customer', 'user'])
            ->whereIn('agency_id', $agencyIds);

        // إضافة تصفية المستخدم - عرض مبيعات المستخدم الحالي فقط إلا إذا كان أدمن
        $currentUser = Auth::user();
        $isAgencyAdmin = $currentUser->hasRole('agency-admin');
        
        if (!$isAgencyAdmin) {
            $query->where('user_id', $currentUser->id);
        }

        $sales = $query->when($request->search, function ($query) use ($request) {
                $term = '%' . $request->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('beneficiary_name', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('pnr', 'like', $term);
                });
            })
            ->when($request->serviceTypeFilter, fn($q) => $q->where('service_type_id', $request->serviceTypeFilter))
            ->when($request->providerFilter, fn($q) => $q->where('provider_id', $request->providerFilter))
            ->when($request->accountFilter, fn($q) => $q->where('customer_id', $request->accountFilter))
            ->when($request->pnrFilter, fn($q) => $q->where('pnr', 'like', '%' . $request->pnrFilter . '%'))
            ->when($request->referenceFilter, fn($q) => $q->where('reference', 'like', '%' . $request->referenceFilter . '%'))
            ->when($request->startDate, fn($q) => $q->whereDate('created_at', '>=', $request->startDate))
            ->when($request->endDate, fn($q) => $q->whereDate('created_at', '<=', $request->endDate))
            ->orderBy($request->sortField ?? 'created_at', $request->sortDirection ?? 'desc')
            ->get();

        return [
            'agency' => $agency,
            'sales' => $sales,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'totalSales' => $sales->sum('usd_sell')
        ];
    }
}
