<?php

namespace App\Livewire\Agency\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Provider;
use App\Models\DynamicListItem;
use Livewire\Attributes\Layout;
use App\Exports\SalesReportExport;
use Spatie\Browsershot\Browsershot;
use Maatwebsite\Excel\Facades\Excel;
use App\Tables\SalesTable;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.agency')]
class SalesReport extends Component
{
    use WithPagination;

    // خصائص الفلاتر
    public $search = '';
    public $serviceTypeFilter = '';
    public $providerFilter = '';
    public $accountFilter = '';
    public $startDate = '';
    public $endDate = '';
    public $pnrFilter = '';
    public $referenceFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    // بيانات إضافية
    public $serviceTypes = [];
    public $providers = [];
    public $customers = [];
    public $totalSales = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'serviceTypeFilter' => ['except' => ''],
        'providerFilter' => ['except' => ''],
        'accountFilter' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'pnrFilter' => ['except' => ''],
        'referenceFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->loadInitialData();
    }

    public function exportToPdf()
    {
        $data = $this->prepareReportData();
        $agency = $data['agency'];

        // ── الشعار → base64
        $logoData = null;
        $mime = null;
        if (!empty($agency->logo)) {
            $path = storage_path('app/public/' . ltrim($agency->logo, '/'));
            if (is_file($path)) {
                $mime = mime_content_type($path) ?: 'image/png';
                $logoData = base64_encode(file_get_contents($path));
            }
        }

        $html = view('reports.sales-full-pdf', [
            'sales' => $data['sales'],
            'totalSales' => $data['totalSales'],
            'agency' => $agency,
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'fields' => $data['fields'],
            'headers' => $data['headers'],
            'formats' => $data['formats'],
            // ⬅️ جديد
            'logoData' => $logoData,
            'mime' => $mime,
        ])->render();

        return response(
            \Spatie\Browsershot\Browsershot::html($html)
                ->format('A4')->emulateMedia('screen')->noSandbox()->waitUntilNetworkIdle()->pdf()
        )->withHeaders([
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="sales-full-report.pdf"',
                ]);
    }



    public function exportToExcel()
    {
        $data = $this->prepareReportData();
        $sales = $data['sales'];
        $currency = $data['agency']->currency ?? 'USD';

        // حمّل كل العلاقات المستخدمة في SalesTable
        $sales->load([
            'user:id,name',
            'provider:id,name',
            'service:id,label',
            'customer:id,name',
            'agency:id,name,currency',
        ]);
        // مجموع التحصيلات
        $sales->loadSum('collections', 'amount');

        // قيَم مشتقة كما في الواجهة
        foreach ($sales as $s) {
            $paidFromSales = $s->amount_paid ?? 0;
            $paidFromCollections = $s->collections_sum_amount ?? 0;
            if (in_array($s->status, ['Refund-Full', 'Refund-Partial'])) {
                $paidFromSales = 0;
                $paidFromCollections = 0;
            }
            $s->total_paid = $paidFromSales + $paidFromCollections;
            $s->remaining_payment = ($s->usd_sell ?? 0) - $s->total_paid;
        }

        // نفس أعمدة الواجهة، وأخفي actions و duplicatedBy.name فقط
        $cols = array_values(array_filter(
            \App\Tables\SalesTable::columns(true, true),
            fn($c) => !in_array($c['key'] ?? '', ['actions', 'duplicatedBy.name'])
        ));
        $fields = array_map(fn($c) => $c['key'], $cols);  // ← لا نغيّرها
        $headers = [];
        $formats = [];
        foreach ($cols as $c) {
            $headers[$c['key']] = $c['label'] ?? $c['key'];
            if (isset($c['format']))
                $formats[$c['key']] = $c['format'];
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SalesReportExport([
                'sales' => $sales,
                'fields' => $fields,
                'headers' => $headers,
                'formats' => $formats,
                'currency' => $currency,
            ]),
            'sales-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
    protected function prepareReportData()
    {
        $user = auth()->user();
        $agency = $user->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $query = Sale::query()

            ->with([
                'user:id,name',
                'provider:id,name',
                'service:id,label',
                'customer:id,name',
                'duplicatedBy:id,name',
            ])
            ->whereIn('agency_id', $agencyIds)
            // فلترة باسم الموظف (كما في الحسابات)
            ->when($this->search, function ($q) {
                $t = '%' . $this->search . '%';
                $q->whereHas('user', fn($u) => $u->where('name', 'like', $t));
            })
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
            // PNR / Reference مع تحسين like القصير
            ->when($this->pnrFilter, function ($q) {
                $t = trim($this->pnrFilter);
                $q->where('pnr', 'like', mb_strlen($t) >= 3 ? "%$t%" : "$t%");
            })
            ->when($this->referenceFilter, function ($q) {
                $t = trim($this->referenceFilter);
                $q->where('reference', 'like', mb_strlen($t) >= 3 ? "%$t%" : "$t%");
            })
            // تواريخ محسّنة لاستفادة الفهرس
            ->when(
                $this->startDate && $this->endDate,
                fn($q) => $q->whereBetween('sale_date', [$this->startDate, $this->endDate])
            )
            ->when(
                $this->startDate && !$this->endDate,
                fn($q) => $q->where('sale_date', '>=', $this->startDate)
            )
            ->when(
                !$this->startDate && $this->endDate,
                fn($q) => $q->where('sale_date', '<=', $this->endDate)
            )
            ->orderBy($this->sortField, $this->sortDirection);


        $sales = $query->get();

        // جهّز الأعمدة للـ PDF بنفس أسلوب الحسابات
        $columns = array_values(array_filter(
            \App\Tables\SalesTable::columns(true, true),
            fn($c) => ($c['key'] ?? '') !== 'actions'
        ));
        $fields = array_map(fn($c) => $c['key'], $columns);
        $headers = [];
        $formats = [];
        foreach ($columns as $c) {
            $headers[$c['key']] = $c['label'] ?? $c['key'];
            if (isset($c['format']))
                $formats[$c['key']] = $c['format'];
        }

        return [
            'agency' => $agency,
            'sales' => $sales,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'filters' => [
                'search' => $this->search,
                'serviceTypeFilter' => $this->serviceTypeFilter,
                'providerFilter' => $this->providerFilter,
                'accountFilter' => $this->accountFilter,
                'pnrFilter' => $this->pnrFilter,
                'referenceFilter' => $this->referenceFilter,
            ],
            'totalSales' => $sales->sum('usd_sell'),
            // جديد للـ PDF
            'fields' => $fields,
            'headers' => $headers,
            'formats' => $formats,
        ];
    }
    protected function loadInitialData()
    {
        $this->serviceTypes = DynamicListItem::whereHas('list', function ($q) {
            $q->where('name', 'قائمة الخدمات')
                ->where(function ($query) {
                    $query->where('created_by_agency', auth()->user()->agency_id)
                        ->orWhereNull('created_by_agency');
                });
        })->orderBy('order')->get();

        $this->providers = Provider::where('agency_id', auth()->user()->agency_id)->get();
        $this->customers = Customer::where('agency_id', auth()->user()->agency_id)->latest()->get();
    }
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }
    public function resetFilters()
    {
        $this->reset([
            'search',
            'serviceTypeFilter',
            'providerFilter',
            'accountFilter',
            'startDate',
            'endDate',
            'pnrFilter',
            'referenceFilter'
        ]);
    }
    public function render()
    {
        // بعد — استبدل كتلة الاستعلام حتى إرجاع الـ view
        $user = auth()->user();
        $agency = $user->agency;

        $agencyIds = $agency->parent_id
            ? [$agency->id]
            : array_merge([$agency->id], $agency->branches()->pluck('id')->toArray());

        $salesQuery = Sale::query()
            ->with([
                'user:id,name',
                'provider:id,name',
                'service:id,label',
                'customer:id,name',
                'duplicatedBy:id,name',
            ])
            ->whereIn('agency_id', $agencyIds)
            ->when($this->search, function ($q) {
                $t = '%' . $this->search . '%';
                $q->whereHas('user', fn($u) => $u->where('name', 'like', $t));
            })
            ->when($this->serviceTypeFilter, fn($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->providerFilter, fn($q) => $q->where('provider_id', $this->providerFilter))
            ->when($this->accountFilter, fn($q) => $q->where('customer_id', $this->accountFilter))
            ->when($this->pnrFilter, function ($q) {
                $t = trim($this->pnrFilter);
                $q->where('pnr', 'like', mb_strlen($t) >= 3 ? "%$t%" : "$t%");
            })
            ->when($this->referenceFilter, function ($q) {
                $t = trim($this->referenceFilter);
                $q->where('reference', 'like', mb_strlen($t) >= 3 ? "%$t%" : "$t%");
            })
            ->when(
                $this->startDate && $this->endDate,
                fn($q) => $q->whereBetween('sale_date', [$this->startDate, $this->endDate])
            )
            ->when(
                $this->startDate && !$this->endDate,
                fn($q) => $q->where('sale_date', '>=', $this->startDate)
            )
            ->when(
                !$this->startDate && $this->endDate,
                fn($q) => $q->where('sale_date', '<=', $this->endDate)
            );



        $this->totalSales = $salesQuery->clone()->sum('usd_sell');

        $sales = $salesQuery
            ->withSum('collections', 'amount')
            ->orderBy($this->sortField, $this->sortDirection)
            ->simplePaginate(10);

        $sales->each(function ($sale) {
            $paidFromSales = $sale->amount_paid ?? 0;
            $paidFromCollections = $sale->collections_sum_amount ?? 0; // استخدم withSum
            if (in_array($sale->status, ['Refund-Full', 'Refund-Partial'])) {
                $paidFromSales = 0;
                $paidFromCollections = 0;
            }
            $sale->total_paid = $paidFromSales + $paidFromCollections;
            $sale->remaining_payment = ($sale->usd_sell ?? 0) - $sale->total_paid;
        });

        return view('livewire.agency.reportsView.sales-report', [
            'sales' => $sales,
            'columns' => \App\Tables\SalesTable::columns(true, true),
            'totalSales' => $this->totalSales,
            'serviceTypes' => $this->serviceTypes,
            'providers' => $this->providers,
            'customers' => $this->customers,
        ]);

    }
    public function printPdf($saleId)
    {
        $sale = Sale::with(['customer', 'provider', 'user', 'service'])->findOrFail($saleId);

        $html = view('reports.sale-single', compact('sale'))->render();

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->margins(10, 10, 10, 10)
            ->pdf();

        return response()->streamDownload(
            fn() => print ($pdf),
            'sale-details-' . $sale->id . '.pdf'
        );
    }
}
