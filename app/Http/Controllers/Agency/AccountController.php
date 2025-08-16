<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Sale;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AccountsExport;

class AccountController extends Controller
{
    public function generatePdfReport(Request $request)
    {
        $pdfPath = storage_path('app/public/sales_report.pdf');

        $user = Auth::user();
        $agency = $user->agency;
        $query = Sale::with(['customer', 'serviceType', 'provider', 'intermediary', 'account'])
            ->where('agency_id', $agency->id)
            ->when(!$user->hasRole('agency-admin'), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        // فلترة التاريخ
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        // باقي الفلاتر
        if ($request->filled('service_type')) {
            $query->where('service_type_id', $request->service_type);
        }

        if ($request->filled('provider')) {
            $query->where('provider_id', $request->provider);
        }

        if ($request->filled('account')) {
            $query->where('account_id', $request->account);
        }

        if ($request->filled('pnr')) {
            $query->where('pnr', 'like', '%' . $request->pnr . '%');
        }

        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }

        $sales = $query->latest()->get();

        // تحديد الحقول المراد عرضها
        $fields = $request->input('fields', [
            'sale_date',
            'beneficiary_name',
            'serviceType',
            'route',
            'pnr',
            'reference',
            'usd_sell',
            'usd_buy',
            'provider',
            'customer_id',
        ]);

        // تحضير HTML
        $html = view('reports.accounts-pdf', [
            'sales' => $sales,
            'agency' => $agency,
            'startDate' => $request->start_date,
            'endDate' => $request->end_date,
            'fields' => $fields
        ])->render();

        // توليد PDF عبر Browsershot
        $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
            ? 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
            : '/usr/bin/google-chrome');

        Browsershot::html($html)
            ->setChromePath($chromePath)
            ->noSandbox()
            ->setOption('args', ['--disable-dev-shm-usage'])
            ->format('A4')
            ->landscape()
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->savePdf($pdfPath);

        return response()->download($pdfPath)->deleteFileAfterSend();

        return response()->download($pdfPath)->deleteFileAfterSend();
    }



    public function generateExcelReport(Request $request)
    {
        $fields = $request->has('fields') ? $request->fields : null;
        $filters = $request->only(['start_date', 'end_date', 'service_type', 'provider', 'account', 'pnr', 'reference']);

        $user = Auth::user();
        $isAgencyAdmin = $user->hasRole('agency-admin');

        if (!$isAgencyAdmin) {
            $filters['user_id'] = $user->id;
        }

        return Excel::download(new AccountsExport($fields, $filters), 'accounts_report_' . now()->format('Y-m-d') . '.xlsx');
    }
}
