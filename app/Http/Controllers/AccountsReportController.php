<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class AccountsReportController extends Controller
{
    public function downloadPdf()
    {
        $data = $this->prepareReportData();

        $html = view('reports.accounts-full-pdf', [
            'sales' => $data['sales'],
            'totalSales' => $data['totalSales'],
            'agency' => $data['agency'],
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
        ])->render();

        return response(
            Browsershot::html($html)
                ->format('A4')
                ->noSandbox()
                ->emulateMedia('screen')
                ->waitUntilNetworkIdle()
                ->pdf()
        )->withHeaders([
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
