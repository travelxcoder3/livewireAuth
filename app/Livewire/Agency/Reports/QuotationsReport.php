<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\{Quotation, User};
use App\Tables\QuotationsReportTable;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\QuotationsReportExport;

class QuotationsReport extends Component
{
    use WithPagination;

    public $userId = null; // اتركها غير مقيّدة
    public ?string $startDate = null;
    public ?string $endDate   = null;
    public int $perPage = 10;

    protected $queryString = [
        'userId'    => ['except' => null],
        'startDate' => ['except' => null],
        'endDate'   => ['except' => null],
        'page'      => ['except' => 1],
    ];

    // حوّل قيمة الـ<select> إلى رقم أو null
    public function updatedUserId($value): void
    {
        $this->userId = ($value === '' || $value === null) ? null : (int) $value;
        $this->resetPage();
    }

    public function exportToExcel()
{
    $uid = ($this->userId === '' || $this->userId === null) ? null : (int) $this->userId;
    return Excel::download(new QuotationsReportExport($uid, $this->startDate, $this->endDate), 'quotations-report.xlsx');
}
    public function updatingStartDate() { $this->resetPage(); }
    public function updatingEndDate()   { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->userId = null;
        $this->startDate = null;
        $this->endDate = null;
        $this->resetPage();
    }

    private function normalizeDate(?string $v): ?string
    {
        if (!$v) return null;
        try { return Carbon::parse($v)->toDateString(); } catch (\Throwable) { return null; }
    }

    private function baseQuery()
    {
        $agencyId = Auth::user()->agency_id;
        $from = $this->normalizeDate($this->startDate);
        $to   = $this->normalizeDate($this->endDate);

        return Quotation::query()
            ->with(['user:id,name'])
            ->withCount('items')
            ->where('agency_id', $agencyId)
            ->when($this->userId !== null && $this->userId !== '', fn($q) => $q->where('user_id', (int) $this->userId))
            ->when($from, fn($q)=>$q->whereDate('quotation_date','>=',$from))
            ->when($to,   fn($q)=>$q->whereDate('quotation_date','<=',$to))
            ->orderByDesc('quotation_date')
            ->orderByDesc('id');
    }

    public function render()
    {
        $rows = $this->baseQuery()->paginate($this->perPage);
        $rows->getCollection()->transform(function ($r) {
            $r->pdf_url  = route('agency.quotations.pdf',  $r->id);
            $r->view_url = route('agency.quotations.view', $r->id);
            return $r;
        });


        $users = User::where('agency_id', Auth::user()->agency_id)
            ->orderBy('name')
            ->get(['id','name']);

       return view('livewire.agency.reportsView.quotations-report', [
                    'rows'    => $rows,
                    'users'   => $users,
                    'columns' => QuotationsReportTable::columns(),
                ])->layout('layouts.agency');

    }
}
