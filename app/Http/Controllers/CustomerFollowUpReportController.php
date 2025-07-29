<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class CustomerFollowUpReportController extends Controller
{
    public function downloadPdf(Request $request)
    {
        Log::info('PDF request filters', $request->all());
        $user = Auth::user();
        $agencyId = $user->agency_id;

        $query = Sale::query()
            ->where('agency_id', $agencyId)
            ->with(['service', 'customer']);
            
        if ($request->filled('serviceTypeFilter')) {
            $query->where('service_type_id', $request->serviceTypeFilter);
        }

        if ($request->filled('serviceDate')) {
            $query->whereDate('service_date', $request->serviceDate);
        }

        $sales = $query->get();

        $html = view('reports.customer-follow-up-pdf', [
            'sales' => $sales,
            'agency' => $user->agency,
            'totalCount' => $sales->count(),
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
                    'Content-Disposition' => 'inline; filename="customer-follow-up-report.pdf"',
                ]);
    }
}
