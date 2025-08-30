<?php

namespace App\Http\Controllers\Agency\Statements;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class ProviderStatementPdfController extends Controller
{
    public function show(Request $request, Provider $provider)
    {
        abort_unless($provider->agency_id === Auth::user()->agency_id, 403);

        $payload = json_decode(base64_decode((string)$request->string('data')), true) ?? [];
        $rows    = $payload['rows']   ?? [];
        $totals  = $payload['totals'] ?? ['count'=>count($rows)];

        $agency = $provider->agency;
        $logoPath = $agency && $agency->logo ? storage_path('app/public/'.$agency->logo) : null;
        $logoDataUrl = ($logoPath && is_file($logoPath))
            ? 'data:'.(mime_content_type($logoPath) ?: 'image/png').';base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $html = view('pdf.agency.statements.provider-statement', [
            'provider'   => $provider,
            'agency'     => $agency,
            'rows'       => $rows,
            'totals'     => $totals,
            'logoDataUrl'=> $logoDataUrl,
        ])->render();

        $tmp = storage_path('app/tmp_provider_stmt_'.uniqid().'.html');
        file_put_contents($tmp, $html);
        $out = storage_path('app/provider_statement_'.$provider->id.'_'.time().'.pdf');

        Browsershot::html(file_get_contents($tmp))
            ->format('A4')->margins(10,8,12,8)->showBackground()->save($out);
        @unlink($tmp);

        $fname = 'provider-statement-'.$provider->id.'-'.now()->format('Ymd').'.pdf';
        return response()->download($out, $fname)->deleteFileAfterSend(true);
    }
}
