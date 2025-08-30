<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class InvoicePrintController extends Controller
{
    /**
     * يطبع مجموعة فواتير بحسب مفاتيح المجموعات Base64 القادمة من Livewire (selectedGroups)
     * route: agency.customer-invoices.print
     */
 // App/Http/Controllers/Agency/InvoicePrintController.php

public function printSelected(Customer $customer, Request $request)
{
    abort_unless($customer->agency_id === Auth::user()->agency_id, 403);

    $sel       = (string) $request->query('sel', '');
    $keys      = json_decode(base64_decode($sel), true) ?? [];
    if (!is_array($keys) || !count($keys)) abort(422, 'لا توجد مجموعات للطباعة.');

    $taxParam  = (float) $request->query('tax', 0);
    $isPercent = (int) $request->query('is_percent', 1) === 1;

    $pagesHtml = collect($keys)->map(function ($groupKey) use ($customer, $taxParam, $isPercent) {
        $sales = \App\Models\Sale::query()
            ->where(fn($q)=>$q->where('sale_group_id',$groupKey)->orWhere('id',$groupKey))
            ->where('customer_id',$customer->id)
            ->with(['agency','customer','user','service','provider','collections'])
            ->orderBy('created_at')
            ->get();

        if ($sales->isEmpty()) return null;

        $sale           = $sales->last();
        $invoice        = null;
        $agencyCurrency = Auth::user()->agency->currency ?? 'USD';

        // الضريبة لهذا السطر
        $base   = (float) ($sale->usd_sell ?? 0);
        $tax    = $isPercent ? round($base * ($taxParam/100), 2) : $taxParam;

        return View::make('pdf.customer-sale', compact('sale','invoice','agencyCurrency'))
            ->with('tax', $tax)
            ->render();
    })->filter()->values();

    if ($pagesHtml->isEmpty()) abort(404, 'لم يتم العثور على بيانات للفواتير المطلوبة.');

    $html = $pagesHtml->implode('<div style="page-break-after: always;"></div>');

    $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
        ? 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe' : '/usr/bin/google-chrome');

    $file    = 'customer-invoices-'.Str::uuid().'.pdf';
    $pdfPath = storage_path('app/public/'.$file);
    if (!is_dir(dirname($pdfPath))) @mkdir(dirname($pdfPath), 0755, true);

    Browsershot::html($html)->setChromePath($chromePath)->noSandbox()
        ->setOption('args',['--disable-dev-shm-usage'])->format('A4')
        ->margins(10,10,10,10)->waitUntilNetworkIdle()->timeout(60)->savePdf($pdfPath);

    return response()->download($pdfPath)->deleteFileAfterSend(true);
}



public function printBulk(Customer $customer, Request $request)
{
    abort_unless($customer->agency_id === Auth::user()->agency_id, 403);

    $sel       = (string) $request->query('sel', '');
    $keys      = json_decode(base64_decode($sel), true) ?? [];
    if (!is_array($keys) || !count($keys)) abort(422, 'لا توجد مجموعات للطباعة.');

    $taxParam  = (float) $request->query('tax', 0);
    $isPercent = (int) $request->query('is_percent', 1) === 1;

    // اجلب أحدث صف من كل مجموعة
    $sales = collect($keys)->map(function ($groupKey) use ($customer) {
        $rows = \App\Models\Sale::query()
            ->where(fn($q)=>$q->where('sale_group_id',$groupKey)->orWhere('id',$groupKey))
            ->where('customer_id',$customer->id)
            ->with(['agency','service'])
            ->orderBy('created_at')
            ->get();
        if ($rows->isEmpty()) return null;
        $sale = $rows->last();
        $sale->pivot = (object)['base_amount' => (float)($sale->usd_sell ?? 0)];
        return $sale;
    })->filter()->values();

    if ($sales->isEmpty()) abort(404, 'لا توجد بيانات مطابقة.');

    $subtotal = (float) $sales->sum(fn($s)=>(float)($s->pivot->base_amount ?? $s->usd_sell));
    $taxTotal = $isPercent ? round($subtotal * ($taxParam/100), 2) : $taxParam;
    $grand    = $subtotal + $taxTotal;

    $invoice = (object)[
        'invoice_number' => 'BULK-'.now()->format('Ymd-His'),
        'date'           => now()->toDateString(),
        'entity_name'    => $customer->name,
        'agency'         => Auth::user()->agency,
        'user'           => Auth::user(),
        'sales'          => $sales,
        'subtotal'       => $subtotal,
        'tax_total'      => $taxTotal,
        'grand_total'    => $grand,
    ];

    $html = View::make('pdf.customer-bulk', compact('invoice'))->render();

    $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
        ? 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe' : '/usr/bin/google-chrome');

    $file    = 'customer-bulk-'.Str::uuid().'.pdf';
    $pdfPath = storage_path('app/public/'.$file);
    if (!is_dir(dirname($pdfPath))) @mkdir(dirname($pdfPath), 0755, true);

    Browsershot::html($html)->setChromePath($chromePath)->noSandbox()
        ->setOption('args',['--disable-dev-shm-usage'])->format('A4')
        ->margins(10,10,10,10)->waitUntilNetworkIdle()->timeout(60)->savePdf($pdfPath);

    return response()->download($pdfPath)->deleteFileAfterSend(true);
}

}
