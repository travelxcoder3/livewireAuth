<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\DynamicListItem;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Browsershot\Browsershot;
use App\Exports\CustomerFollowUpReportExport;
use App\Tables\SalesTable;

#[Layout('layouts.agency')]
class CustomerFollowUpReport extends Component
{
    use WithPagination;

    // خصائص الفلاتر
    public $serviceTypeFilter = '';
    public $serviceDate = '';

    // البيانات المحملة
    public $serviceTypes = [];
    public $columns = [];
    public $totalCount = 0;

    protected $queryString = [
        'serviceTypeFilter' => ['except' => ''],
        'serviceDate' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        $this->loadInitialData();
    }

    protected function loadInitialData()
    {
        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
                ->where(fn($q2) => $q2->where('created_by_agency', auth()->user()->agency_id)
                    ->orWhereNull('created_by_agency'));
        })->orderBy('order')->get();
    }

    public function resetFilters()
    {
        $this->reset(['serviceTypeFilter', 'serviceDate']);
    }

    public function render()
    {
        $data = $this->prepareReportData();
        $sales = $data['sales'];
        $this->totalCount = $data['totalCount'];

        return view('livewire.agency.reportsView.customer-follow-up-report', [
            'sales' => $sales,
            'serviceTypes' => $this->serviceTypes,
            'columns' => $this->columns,
            'totalCount' => $this->totalCount,
        ]);
    }

    protected function prepareReportData()
    {
        $agency = Auth::user()->agency;
        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $query = Sale::with(['customer', 'service'])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->serviceDate, fn($q) => $q->whereDate('service_date', $this->serviceDate));

        $totalCount = $query->count();
        $sales = $query->orderBy('service_date', 'desc')->paginate(10);

        return [
            'sales' => $sales,
            'totalCount' => $totalCount,
        ];
    }

    public function exportToExcel()
    {
        $data = $this->prepareReportData();

        return Excel::download(
            new CustomerFollowUpReportExport([
                'sales' => $data['sales']->items()
            ]),
            'customer-follow-up-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportToPdf()
    {
        $data = $this->prepareReportData();
        $salesCollection = $data['sales']->items();

        $html = view('reports.customer-follow-up-pdf', [
            'sales' => $salesCollection,
            'totalCount' => $data['totalCount'],
        ])->render();

        // ✅ إصلاح الترميز قبل الإرسال لـ Browsershot
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        return response(
            Browsershot::html($html)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->emulateMedia('screen')
                ->noSandbox()
                ->waitUntilNetworkIdle()
                ->pdf()
        )->withHeaders([
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="customer-follow-up-report.pdf"',
                ]);
    }

    public function printPdf($id)
    {
        $sale = Sale::with(['agency', 'customer', 'provider', 'service'])->findOrFail($id);

        $html = view('reports.customer-follow-up-single', [
            'sale' => $sale,
        ])->render();

        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->emulateMedia('screen')
            ->noSandbox()
            ->waitUntilNetworkIdle()
            ->pdf();

        return response()->streamDownload(
            fn() => print ($pdf),
            'follow-up-single-' . $sale->id . '.pdf'
        );
    }



}
