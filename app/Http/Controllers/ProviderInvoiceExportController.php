<?php
// app/Http/Controllers/ProviderInvoiceExportController.php
namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
class ProviderInvoiceExportController extends Controller
{
    public function export(Provider $provider, Request $request)
    {
        $ids = array_filter(explode(',', (string)$request->query('ids')));
        // جهّز البيانات بحسب $ids ...
     // جهّز صفوف المجموعات من المزوّد حسب selected ids
$idsArr = array_filter(explode(',', (string)$request->query('ids')));
$sales  = $provider->sales()
    ->with(['service','customer'])
    ->where(function($q) use ($idsArr){
        $q->whereIn('sale_group_id', $idsArr)
          ->orWhereIn('id', $idsArr);
    })->get();

$refundStatuses = ['refund-full','refund_full','refund-partial','refund_partial','refunded','refund'];
$voidStatuses   = ['void','cancel','canceled','cancelled'];

$groups = $sales->groupBy(fn($s)=> $s->sale_group_id ?? $s->id)->map(function($rows) use($refundStatuses,$voidStatuses){
    $s = $rows->sortByDesc('created_at')->first();

    $costTotalTrue = $rows->reject(function ($x) use ($refundStatuses,$voidStatuses){
        $st = mb_strtolower(trim($x->status ?? ''));
        return in_array($st,$refundStatuses,true) || in_array($st,$voidStatuses,true);
    })->sum('usd_buy');

    $refundTotal = $rows->filter(function ($x) use ($refundStatuses,$voidStatuses){
        $st = mb_strtolower(trim($x->status ?? ''));
        return in_array($st,$refundStatuses,true) || in_array($st,$voidStatuses,true);
    })->sum(fn($x)=>abs($x->usd_buy));

    return (object)[
        'beneficiary_name' => $s->beneficiary_name ?? optional($s->customer)->name ?? '—',
        'sale_date'        => $s->sale_date,
        'service_label'    => $s->service->label ?? '-',
        'cost_total_true'  => (float)$costTotalTrue,
        'refund_total'     => (float)$refundTotal,
        'net_cost'         => (float)($costTotalTrue - $refundTotal),
    ];
})->values();

$data = [
    'provider' => $provider,
    'groups'   => $groups,
    'currency' => Auth()->user()->agency->currency ?? 'USD',
    'theme'    => strtolower(Auth()->user()->agency->theme_color ?? 'emerald'),
];


// احذف DomPDF إن كنت لن تستخدمه:
// use Barryvdh\DomPDF\Facade\Pdf;

$html = view('pdf.provider-groups', $data)->render();

/** تأكد من مجلد التخزين ثم احفظ وارجِع الملف */
$dir  = storage_path('app/tmp');
File::ensureDirectoryExists($dir);
$path = $dir.'/provider-invoices-'.uniqid().'.pdf';

Browsershot::html($html)
    ->format('A4')
    ->landscape()
    ->margins(10,10,10,10)
    ->save($path);

return response()->download($path, 'provider-invoices.pdf')->deleteFileAfterSend(true);



    }
}
