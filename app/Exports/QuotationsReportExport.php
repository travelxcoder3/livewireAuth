<?php

namespace App\Exports;

use App\Models\Quotation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuotationsReportExport implements FromCollection, WithHeadings, WithMapping
{
    public ?int $userId;
    public ?string $startDate;
    public ?string $endDate;

    public function __construct(?int $userId = null, ?string $startDate = null, ?string $endDate = null)
    {
        $this->userId    = $userId;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    private function normalize(?string $v): ?string
    {
        if (!$v) return null;
        try { return Carbon::parse($v)->toDateString(); } catch (\Throwable) { return null; }
    }

    public function collection(): Collection
    {
        $agencyId = Auth::user()->agency_id;
        $from = $this->normalize($this->startDate);
        $to   = $this->normalize($this->endDate);

        return Quotation::query()
            ->with(['user:id,name'])
            ->withCount('items')
            ->where('agency_id', $agencyId)
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($from, fn($q)=>$q->whereDate('quotation_date','>=',$from))
            ->when($to,   fn($q)=>$q->whereDate('quotation_date','<=',$to))
            ->orderByDesc('quotation_date')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'رقم العرض',
            'التاريخ',
            'العميل',
            'العملة',
            'نسبة الضريبة %',
            'الإجمالي الفرعي',
            'الضريبة',
            'الإجمالي الكلي',
            'المستخدم',
            'عدد البنود',
        ];
    }

    public function map($q): array
    {
        return [
            $q->quotation_number ?? '',
            $q->quotation_date ? Carbon::parse($q->quotation_date)->format('Y-m-d') : '',
            $q->to_client ?? '',
            $q->currency ?? '',
            $q->tax_rate ?? 0,
            number_format((float)$q->subtotal, 2, '.', ''),
            number_format((float)$q->tax_amount, 2, '.', ''),
            number_format((float)$q->grand_total, 2, '.', ''),
            $q->user?->name ?? '',
            (int)($q->items_count ?? 0),
        ];
    }
}
