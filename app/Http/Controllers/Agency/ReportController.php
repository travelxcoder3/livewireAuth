<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;
use App\Models\Sale;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function salesPdf(Request $request)
{
    $pdfPath = storage_path('app/public/sales_report.pdf');

    $user = Auth::user();
    $agency = $user->agency;

    $query = Sale::with(['customer', 'serviceType', 'provider', 'account'])
    ->where('agency_id', $agency->id); // ✅ عرض مبيعات الوكالة فقط
    // إضافة تصفية المستخدم - عرض مبيعات المستخدم الحالي فقط إلا إذا كان أدمن
    $isAgencyAdmin = $user->hasRole('agency-admin');
    
    if (!$isAgencyAdmin) {
        $query->where('user_id', $user->id);
    }

    // فلترة بالتاريخ
    if ($request->filled('start_date')) {
        $query->whereDate('sale_date', '>=', $request->start_date);
    }

    if ($request->filled('end_date')) {
        $query->whereDate('sale_date', '<=', $request->end_date);
    }

    $sales = $query->latest()->get();

    // الحقول المطلوبة من الطلب
    $fields = $request->input('fields', []);

    // إذا لم يتم تحديد أي حقول، استخدم القيم الافتراضية
   // الحقول الافتراضية - أضف الحقول الجديدة هنا
    if (empty($fields)) {
        $fields = [
            'sale_date', 'beneficiary_name', 'customer', 'serviceType',
            'provider', 'customer_via', 'usd_buy', 'usd_sell',
            'sale_profit', 'amount_received', 'account',
            'reference', 'pnr', 'route',
            'status', 'payment_method', 'payment_type', 'receipt_number',
            'phone_number', 'commission', 'amount_paid', 'depositor_name',
            'service_date', 'expected_payment_date'
        ];
    }



    $html = view('reports.sales-pdf', [
        'sales' => $sales,
        'agency' => $agency,
        'startDate' => $request->start_date,
        'endDate' => $request->end_date,
        'fields' => $fields
    ])->render();

     $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
        ? 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
        : '/usr/bin/google-chrome');

    Browsershot::html($html)
        ->setChromePath($chromePath)
        ->noSandbox()
        ->setOption('args', ['--disable-dev-shm-usage'])
        ->format('A4')
        ->landscape()
        ->timeout(60)
        ->waitUntilNetworkIdle()
        ->savePdf($pdfPath);

    return response()->download($pdfPath)->deleteFileAfterSend();
}
}
