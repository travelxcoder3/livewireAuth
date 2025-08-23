<?php

namespace App\Http\Controllers\Agency\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\Quotation;
use Spatie\Browsershot\Browsershot;

class QuotationReportsController extends Controller
{
    private function normalize(?string $v): ?string
    {
        if (!$v) return null;
        try { return Carbon::parse($v)->toDateString(); } catch (\Throwable) { return null; }
    }

    public function quotationsPdf(Request $req)
    {
        $agencyId = Auth::user()->agency_id;

        $userId = $req->filled('userId') ? (int)$req->input('userId') : null;
        $from   = $this->normalize($req->input('startDate'));
        $to     = $this->normalize($req->input('endDate'));

        $rows = Quotation::query()
            ->with(['user:id,name'])
            ->withCount('items')
            ->where('agency_id', $agencyId)
            ->when($userId, fn($q)=>$q->where('user_id', $userId))
            ->when($from, fn($q)=>$q->whereDate('quotation_date','>=',$from))
            ->when($to,   fn($q)=>$q->whereDate('quotation_date','<=',$to))
            ->orderByDesc('quotation_date')
            ->orderByDesc('id')
            ->get();

        $html = view('reports.quotations-report-pdf', compact('rows'))->render();

        // إن لزم على ويندوز حدّد مسار كروم:
        // Browsershot::setChromePath('C:\Program Files\Google\Chrome\Application\chrome.exe');

        $pdf = Browsershot::html($html)
            ->showBackground()
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->waitUntilNetworkIdle()
            ->pdf();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="quotations-report.pdf"',
        ]);
    }
}
