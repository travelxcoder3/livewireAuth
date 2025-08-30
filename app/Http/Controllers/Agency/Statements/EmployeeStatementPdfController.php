<?php

namespace App\Http\Controllers\Agency\Statements;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Browsershot\Browsershot;

class EmployeeStatementPdfController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_unless($user->agency_id === Auth::user()->agency_id, 403);

        // فك الحمولة
        $payload = json_decode(base64_decode((string)$request->string('data')), true) ?? [];
        $rows    = $payload['rows']   ?? [];
        $totals  = $payload['totals'] ?? [];

        // حساب ملخص افتراضي إن لم يُمرَّر
        if (empty($totals)) {
            $totals = [
                'count'        => count($rows),
                'debit'        => array_sum(array_column($rows, 'debit')),
                'credit'       => array_sum(array_column($rows, 'credit')),
                'last_balance' => end($rows)['balance'] ?? 0,
            ];
        }

        // شعار الوكالة كـ data URL
        $agency = $user->agency;
        $logoPath = $agency && $agency->logo ? storage_path('app/public/'.$agency->logo) : null;
        $logoDataUrl = ($logoPath && is_file($logoPath))
            ? 'data:'.(mime_content_type($logoPath) ?: 'image/png').';base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        // توليد HTML من Blade الموظف
        $html = view('pdf.agency.statements.employee-statement', [
            'employee'    => $user,
            'agency'      => $agency,
            'logoDataUrl' => $logoDataUrl,
            'rows'        => $rows,
            'totals'      => $totals,
        ])->render();

        // ملف مؤقت + إخراج
        $tmpHtml = storage_path('app/tmp_emp_stmt_'.uniqid().'.html');
        file_put_contents($tmpHtml, $html);
        $out = storage_path('app/employee_statement_'.$user->id.'_'.time().'.pdf');

        // PDF عبر Browsershot
        Browsershot::html(file_get_contents($tmpHtml))
            ->format('A4')
            ->margins(10, 8, 12, 8)
            ->showBackground()
            ->save($out);

        @unlink($tmpHtml);

        $fname = 'employee-statement-'.($user->name ?? $user->user_name ?? 'user').'-'.now()->format('Ymd').'.pdf';
        return response()->download($out, $fname)->deleteFileAfterSend(true);
    }
}
