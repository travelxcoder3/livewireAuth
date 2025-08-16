<?php
namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class QuotationController extends Controller
{
    public function view(Quotation $quotation)
    {
      
        $data = [
            'agency'          => $quotation->agency,
            'quotationDate'   => $quotation->quotation_date,
            'quotationNumber' => $quotation->quotation_number,
            'toClient'        => $quotation->to_client,
            'taxName'         => $quotation->tax_name,
            'taxRate'         => (float)$quotation->tax_rate,
            'notes'           => (string)($quotation->notes ?? ''),
            'services'        => $quotation->items->map(fn($i)=>[
                                    'service_name'=>$i->service_name,'description'=>$i->description,'route'=>$i->route,
                                    'service_date'=>$i->service_date,'conditions'=>$i->conditions,'price'=>$i->price,
                                ]),
            'terms' => $quotation->terms->pluck('value')->unique()->values()->all(),
            'currency'        => $quotation->currency ?? ($quotation->agency->currency ?? 'USD'),
            'lang'            => $quotation->lang,
            'total'           => (float)$quotation->subtotal,
            'taxAmount'       => (float)$quotation->tax_amount,
            'grandTotal'      => (float)$quotation->grand_total,
        ];
        return view('reports.quotation-pdf', $data); // يصلح للعرض والطباعة
    }

    public function pdf(Quotation $quotation)
    {
      
        $html = $this->view($quotation)->render();
    $file = storage_path('app/public/quotation_' . $quotation->quotation_number . '.pdf');

    $chromePath = env('BROWSERSHOT_CHROME_PATH', PHP_OS_FAMILY === 'Windows'
        ? 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
        : '/usr/bin/google-chrome');

    Browsershot::html($html)
        ->setChromePath($chromePath)
        ->noSandbox()
        ->setOption('args', ['--disable-dev-shm-usage'])
        ->format('A4')
        ->margins(10,10,10,10)
        ->emulateMedia('screen')
        ->waitUntilNetworkIdle()
        ->timeout(60)
        ->savePdf($file);

    return response()->download($file)->deleteFileAfterSend();
    }
}
