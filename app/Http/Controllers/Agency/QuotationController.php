<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class QuotationController extends Controller
{
    public function download(Request $request)
    {
        $user   = Auth::user();
        $agency = $user->agency;

        $lang = $request->input('lang', 'en'); // أو 'ar' حسب ما تريد كافتراضي

        $rawServices = json_decode($request->input('services_json', '[]'), true) ?: [];
$rawTerms    = json_decode($request->input('terms_json', '[]'), true) ?: [];

$services = collect($rawServices)->filter(function ($s) {
    return trim((string)($s['airline'] ?? '')) !== '' ||
           trim((string)($s['details'] ?? '')) !== '' ||
           (float)($s['price'] ?? 0) > 0;
})->values();


$terms = collect($rawTerms)->map(function ($t) {
    // دعم صيغ متعددة: "نص مباشر" أو { value: "..." } أو مصفوفة
    if (is_array($t)) {
        if (array_key_exists('value', $t)) return (string) $t['value'];
        return trim(implode(' ', array_map('strval', array_values($t))));
    }
    return (string) $t;
})->map(fn($s) => trim($s))
  ->filter(fn($s) => $s !== '')
  ->values();

$data = [
    'agency'          => $agency,
    'quotationDate'   => $request->input('quotation_date'),
    'quotationNumber' => $request->input('quotation_number'),
    'toClient'        => $request->input('to_client'),
    'taxName'         => $request->input('tax_name'),
    'taxRate'         => (float) $request->input('tax_rate', 0),
    'notes'           => (string) $request->input('notes', ''),
    'services'        => $services,
    'terms'           => $terms,
    'currency'        => $agency->currency ?? 'USD',
    'lang'            => $lang,
];


        $total = $data['services']->sum(fn($s) => (float) ($s['price'] ?? 0));
        $taxAmount  = $total * ($data['taxRate'] / 100);
        $grandTotal = $total + $taxAmount;

        $data['total']      = $total;
        $data['taxAmount']  = $taxAmount;
        $data['grandTotal'] = $grandTotal;

        $html = view('reports.quotation-pdf', $data)->render();

        $file = storage_path('app/public/quotation_' . now()->format('Ymd_His') . '.pdf');

        Browsershot::html($html)
            ->setChromePath('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe')
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->emulateMedia('screen')
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->savePdf($file);

        return response()->download($file)->deleteFileAfterSend();
    }

}
