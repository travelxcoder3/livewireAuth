<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use App\Services\ThemeService;

class CustomerInvoicePrintController extends Controller
{
    public function printSelected(Request $request, Customer $customer)
    {
        abort_unless($customer->agency_id === Auth::user()->agency_id, 403);

        // selected groups (base64 JSON)
        $encoded  = $request->query('sel', '');
        $selected = json_decode(base64_decode($encoded), true) ?? [];

        $selectedKeys = collect($selected)
            ->filter(fn ($v) => !is_null($v) && $v !== '')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->values();

        if ($selectedKeys->isEmpty()) {
            return back()->with('error', 'لا توجد مستفيدين محددين للطباعة.');
        }

        // build grouped data
        $sales   = $customer->sales()->with(['collections', 'service'])->get();
        $grouped = $sales->groupBy(fn ($s) => (string) ($s->sale_group_id ?? $s->id));

        $reports = collect();
        foreach ($selectedKeys as $key) {
            if (!$grouped->has($key)) continue;

            $salesOfGroup = $grouped->get($key);
            $s         = $salesOfGroup->first();
            $collected = $salesOfGroup->sum(fn ($x) => $x->collections->sum('amount'));
            $paid      = $salesOfGroup->sum('amount_paid');
            $total     = $collected + $paid;

            $reports->push((object) [
                'group_key'        => $key,
                'beneficiary_name' => $s->beneficiary_name,
                'sale_date'        => $s->sale_date,
                'service_label'    => $s->service->label ?? '-',
                'usd_sell'         => $s->usd_sell,
                'total_paid'       => $total,
                'remaining'        => $s->usd_sell - $total,
                'scenarios'        => $salesOfGroup->map(fn ($sale) => [
                    'date'        => $sale->sale_date,
                    'usd_sell'    => $sale->usd_sell,
                    'amount_paid' => $sale->amount_paid,
                    'status'      => $sale->status,
                    'note'        => $sale->reference ?? '-',
                ]),
                'collections'      => $salesOfGroup->flatMap->collections->map(fn ($col) => [
                    'amount'       => $col->amount,
                    'payment_date' => $col->payment_date,
                    'note'         => $col->note,
                ]),
            ]);
        }

        abort_if($reports->isEmpty(), 404, 'لا توجد بيانات للطباعة.');

        $totals = [
            'count'      => $reports->count(),
            'usd_sell'   => $reports->sum('usd_sell'),
            'total_paid' => $reports->sum('total_paid'),
            'remaining'  => $reports->sum(fn ($r) => max($r->remaining, 0)),
        ];

        // بيانات الوكالة + الشعار
        $agency = Auth::user()->agency;

        $logoDataUrl = null;
        if ($agency?->logo) {
            $logoPath = Storage::disk('public')->path($agency->logo);
            if (is_file($logoPath)) {
                $ext = pathinfo($logoPath, PATHINFO_EXTENSION) ?: 'png';
                $logoDataUrl = 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($logoPath));
            }
        }

        // 🔹 ألوان الثيم الديناميكي عبر ThemeService (قيم RGB كنص "r,g,b")
        $themeName = strtolower($agency->theme_color ?? 'emerald');
        $colors    = ThemeService::getCurrentThemeColors($themeName);
        // نتوقع مفاتيح مثل: primary-100 / primary-500 / primary-600 / primary-700 / primary-800

        // اسم ملف وإخراج PDF
        $slug     = Str::slug($customer->name, '-');
        $filename = "detailed-invoices-customer-{$customer->id}-{$slug}-" . now()->format('Ymd-His') . '-' . Str::random(6) . ".pdf";

        $saveDir = storage_path('app/public/reports');
        if (!is_dir($saveDir)) @mkdir($saveDir, 0775, true);
        $fullPath = $saveDir . DIRECTORY_SEPARATOR . $filename;

        $html = view('pdf.customer-invoices-bulk', [
            'customer'    => $customer,
            'agency'      => $agency,
            'logoDataUrl' => $logoDataUrl,
            'colors'      => $colors,   // ← نمرر ألوان الثيم كـ RGB
            'reports'     => $reports,
            'totals'      => $totals,
        ])->render();

        Browsershot::html($html)
            ->format('A4')
            ->margins(12, 10, 14, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->save($fullPath);

        return response()->download($fullPath)->deleteFileAfterSend(true);
    }
}
