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
        $data = $this->prepareReportData($request);

        $html = view('reports.sales-full-pdf', [
            'sales' => $data['sales'],
            'totalSales' => $data['totalSales'],
            'agency' => $data['agency'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
        ])->render();

        return response(
            Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->emulateMedia('screen')
                ->noSandbox()
                ->waitUntilNetworkIdle()
                ->pdf()
        )->withHeaders([
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sales-full-report.pdf"',
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

        return Excel::download(
            new SalesReportExport([
                'sales' => $data['sales']
            ]),
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

        $sales = Sale::with(['service', 'provider', 'customer', 'user'])
            ->whereIn('agency_id', $agencyIds)
            ->when($request->search, function ($query) use ($request) {
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
