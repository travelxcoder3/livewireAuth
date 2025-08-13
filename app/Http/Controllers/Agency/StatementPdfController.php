<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class StatementPdfController extends Controller
{
    public function download(Customer $customer, Request $request)
    {
        abort_unless($customer->agency_id === Auth::user()->agency_id, 403);

        $payload = json_decode(base64_decode($request->string('data')), true) ?? [];
        $rows    = $payload['rows'] ?? [];

        // بيانات الثيم والشعار
        $agency = Auth::user()->agency;
        $logoPath = $agency && $agency->logo ? storage_path('app/public/'.$agency->logo) : null;
        $logoDataUrl = ($logoPath && file_exists($logoPath))
            ? 'data:'.(mime_content_type($logoPath) ?: 'image/png').';base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        // حساب ملخص بسيط
        $totals = [
            'count'                 => count($rows),
            'debit'                 => array_sum(array_column($rows, 'debit')),
            'credit'                => array_sum(array_column($rows, 'credit')),
            'last_balance'          => end($rows)['balance'] ?? 0,
        ];

        // عرض Blade إلى HTML
        $html = view('pdf.agency.statements.customer-statement', [
            'agency'      => $agency,
            'customer'    => $customer,
            'logoDataUrl' => $logoDataUrl,
            'rows'        => $rows,
            'totals'      => $totals,
        ])->render();

        // إنشاء ملف مؤقت
        $tmpHtml = storage_path('app/tmp_statement_'.uniqid().'.html');
        file_put_contents($tmpHtml, $html);
        $out     = storage_path('app/statement_'.$customer->id.'_'.time().'.pdf');

        // توليد PDF
        Browsershot::html(file_get_contents($tmpHtml))
            ->format('A4')
            ->margins(10, 8, 12, 8)
            ->showBackground()
            ->save($out);

        @unlink($tmpHtml);

        return response()->download($out)->deleteFileAfterSend(true);
    }
}
