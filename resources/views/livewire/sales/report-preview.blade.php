@php
    use App\Services\ThemeService;
    use App\Tables\SalesTable;
    use App\Models\Sale;
    $themeName = Auth::check()
        ? (Auth::user()->hasRole('super-admin')
            ? ThemeService::getSystemTheme()
            : strtolower(Auth::user()->agency->theme_color ?? 'emerald'))
        : 'emerald';
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $columns = array_filter(SalesTable::columns(), function($col) {
        return ($col['key'] ?? null) !== 'actions';
    });
    $currentUserId = Auth::id();
    $isAgencyAdmin = Auth::user()->hasRole('agency-admin');
    if ($isAgencyAdmin) {
        $sales = Sale::where('agency_id', Auth::user()->agency_id)->latest()->get();
    } else {
        $sales = Sale::where('user_id', $currentUserId)->latest()->get();
    }
    $agencyName = Auth::user()?->agency?->name ?? 'AnasWare';
    $now = now();
    $totalAmount = $sales->sum('usd_buy');
    $totalProfit = $sales->sum('sale_profit');
    $avgProfit = $sales->count() ? $sales->avg('sale_profit') : 0;
    $agencyCurrency = Auth::user()?->agency?->currency ?? 'USD';
@endphp

<x-app-layout>

<style>
    :root {
        --primary-100: {{ $colors['primary-100'] }};
        --primary-500: {{ $colors['primary-500'] }};
        --primary-600: {{ $colors['primary-600'] }};
    }
    .report-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    .report-title {
        color: rgb(var(--primary-600));
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
        letter-spacing: 1px;
    }
    .report-period {
        color: #444;
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }
    .report-date {
        color: #888;
        font-size: 1rem;
        margin-bottom: 2rem;
        text-align: left;
    }
    .report-agency {
        background: rgba(var(--primary-100), 0.7);
        color: rgb(var(--primary-600));
        font-weight: bold;
        font-size: 1.2rem;
        border-radius: 10px;
        padding: 0.4rem 2.8rem;
        display: inline-block;
        margin-bottom: 1.5rem;
        letter-spacing: 1px;
    }
    .report-stats {
        display: flex;
        gap: 2rem;
        margin-bottom: 2.5rem;
        justify-content: center;
        align-items: stretch;
    }
    .report-stat-card {
        flex: 1 1 0;
        border: 2px solid rgb(var(--primary-500));
        border-radius: 18px;
        padding: 2.2rem 1.2rem 1.2rem 1.2rem;
        background: rgba(var(--primary-100), 0.5);
        min-width: 180px;
        text-align: center;
        font-size: 1.4rem;
        font-weight: bold;
        color: rgb(var(--primary-600));
        box-shadow: 0 2px 12px 0 rgba(var(--primary-500),0.08);
        margin-bottom: 0.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        transition: box-shadow 0.2s;
    }
    .report-stat-card span {
        font-size: 1.1rem;
        color: #666;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .report-stat-card strong {
        font-size: 2.1rem;
        color: rgb(var(--primary-500));
        font-weight: bold;
        margin-bottom: 0.2rem;
        display: block;
    }
    .report-table {
        border: 1.5px solid rgb(var(--primary-500));
        border-radius: 14px;
        overflow: auto;
        background: #fff;
        margin-bottom: 2.5rem;
        box-shadow: 0 2px 12px 0 rgba(var(--primary-500),0.06);
    }
    .report-table table {
        width: 100%;
        border-collapse: collapse;
        font-size: 1.08rem;
        min-width: 900px;
    }
    .report-table th, .report-table td {
        border: 1px solid rgba(var(--primary-500),0.18);
        padding: 0.7rem 0.7rem;
        text-align: center;
    }
    .report-table th {
        background: rgba(var(--primary-100),0.7);
        color: rgb(var(--primary-600));
        font-weight: bold;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .report-table tr:nth-child(even) {
        background: #f9f9f9;
    }
    .report-table tr:hover {
        background: rgba(var(--primary-100),0.2);
    }
    .report-actions {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 2.5rem;
    }
    .report-actions button {
        min-width: 180px;
        font-size: 1.2rem;
        padding: 1rem 0;
        border-radius: 12px;
        font-weight: bold;
        box-shadow: 0 2px 8px 0 rgba(var(--primary-500),0.10);
    }
</style>

<!-- زر الرجوع في أعلى الصفحة خارج الكارد -->
<div class="fixed top-8 left-8 z-50">
    <a href="{{ route('agency.sales.index') }}"
       class="flex items-center gap-2 px-4 py-2 rounded-lg border transition duration-200 text-sm font-medium
              bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))]
              hover:shadow-md hover:text-[rgb(var(--primary-700))]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-180" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        <span>رجوع</span>
    </a>
</div>


<div class="min-h-screen bg-gray-50 flex flex-col items-center justify-start py-10">
    <div class="w-full bg-white rounded-2xl shadow-2xl p-6 sm:p-10 mx-auto">
        <!-- رأس الصفحة: زر الرجوع يسار، العنوان واسم الوكالة في المنتصف -->
        <div class="flex items-center justify-between mb-4">
            <div style="width: 120px;"></div>
            <div class="flex-1 flex flex-col items-center justify-center">
                <div class="report-agency mb-2">{{ $agencyName }}</div>
                <div class="report-title mb-1">تقرير المبيعات</div>
                <div class="report-period">فترة التقرير: بداية النشاط إلى {{ $now->format('Y-m-d') }}</div>
            </div>
        </div>
        @can('sales.report')
        <!-- أزرار التقارير تحت الكارد -->
        <div class="flex justify-end gap-4 mb-8">
            <x-primary-button
                type="button"
                onclick="openReportModal('pdf')"
                padding="px-6 py-2"
                fontSize="text-base"
                class="font-bold"
            >
                تقرير PDF
            </x-primary-button>
            @endcan
            @can('sales.export')
            <x-primary-button
                type="button"
                onclick="openReportModal('excel')"
                padding="px-6 py-2"
                fontSize="text-base"
                class="font-bold"
            >
                تقرير Excel
            </x-primary-button>
            @endcan
        </div>
        <!-- صف: عدد السجلات يمين، تاريخ التقرير يسار -->
        <div class="flex items-center justify-between mb-6 text-gray-700">
            <div class="text-sm">عدد السجلات: {{ $sales->count() }}</div>
            <div class="text-sm">تاريخ التقرير: {{ $now->format('Y-m-d H:i') }}</div>
        </div>
        <!-- كروت الإحصائيات -->
        <div class="border-2 border-[rgb(var(--primary-500))] rounded-lg p-4 mb-8 bg-white flex flex-row items-center justify-between gap-8 w-full">
            <div class="flex flex-col items-end gap-4 font-bold text-[rgb(var(--primary-600))] text-lg">
                <div>إجمالي المبيعات:</div>
                <div>إجمالي الأرباح:</div>
                <div>اجمالي المدفوع:</div>
            </div>
            <div class="flex flex-col items-start gap-4 font-bold text-lg min-w-[120px]">
            <div class="text-[rgb(var(--primary-500))]">{{ $agencyCurrency }}  {{ number_format($sales->sum('usd_sell'), 2) }}</div>
                <div class="text-[rgb(var(--primary-500))]">{{ $agencyCurrency }}  {{ number_format($sales->sum(function($sale) {
        return ($sale->usd_sell ?? 0) - ($sale->usd_buy ?? 0);
    }), 2) }}</div>
                <div class="text-gray-800">{{ $agencyCurrency }}   {{ number_format($sales->sum('amount_paid'), 2) }}</div>
            </div>
        </div>
        <div class="report-table w-full">
            <x-data-table :rows="$sales" :columns="$columns" />
        </div>
    </div>

    <!-- نافذة إعدادات التقرير (fieldsModal) -->
    <div id="fieldsModal" class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center hidden backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
            <button onclick="closeFieldsModal()"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                &times;
            </button>
            <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-600));">
                اختر حقول التقرير
            </h3>
            <form id="customReportForm" method="GET" target="_blank" onsubmit="prepareCustomReport()">
                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                <div class="grid grid-cols-2 gap-4 max-h-96 overflow-y-auto p-2">
                  @foreach([
                            'sale_date' => 'تاريخ البيع',
                            'beneficiary_name' => 'اسم المستفيد',
                            'customer' => 'العميل',
                            'serviceType' => 'الخدمة',
                            'provider' => 'المزود',
                            'customer_via' => 'العميل عبر',
                            'usd_buy' => 'USD Buy',
                            'usd_sell' => 'USD Sell',
                            'sale_profit' => 'الربح',
                            'amount_paid' => 'المبلغ المدفوع أثناء البيع',
                            'reference' => 'المرجع',
                            'pnr' => 'PNR',
                            'route' => 'المسار/ التفاصيل',
                            'status' => 'الحالة',
                            'user' => 'اسم الموظف',
                            'payment_method' => 'حالة الدفع',
                            'payment_type' => 'وسيلة الدفع',
                            'receipt_number' => 'رقم السند',
                            'phone_number' => 'رقم هاتف المستفيد',
                            'commission' => 'مبلغ عمولة العميل',
                            'depositor_name' => 'اسم المودع',
                            'service_date' => 'تاريخ الخدمة',
                            'expected_payment_date' => 'تاريخ الدفع المتوقع',
                        ] as $field => $label)

                    <div class="flex items-center">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer">
                            <input type="checkbox"
                                name="fields[]"
                                value="{{ $field }}"
                                checked
                                class="h-4 w-4 rounded border-gray-300 focus:ring-[rgb(var(--primary-500))] text-[rgb(var(--primary-500))] accent-[rgb(var(--primary-500))]" />
                            <span class="text-gray-700 text-sm">{{ $label }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-center gap-3">
                 <button type="button" onclick="closeFieldsModal()"
                    class="text-[rgb(var(--primary-600))] bg-white border border-gray-300 font-bold px-6 py-2 rounded-lg hover:bg-gray-100 transition duration-150 text-sm shadow-sm">
                    رجوع
                </button>


                <x-primary-button
                    type="submit"
                    padding="px-6 py-2"
                    fontSize="text-sm"
                    class="font-bold shadow-md"
                >
                    تحميل التقرير
                </x-primary-button>

                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentReportType = '';
function openReportModal(type) {
    currentReportType = type;
    document.getElementById('fieldsModal').classList.remove('hidden');
}
function closeFieldsModal() {
    document.getElementById('fieldsModal').classList.add('hidden');
}
function prepareCustomReport() {
    event.preventDefault();
    const form = document.getElementById('customReportForm');
    const startDate = "{{ request('start_date') }}";
    const endDate = "{{ request('end_date') }}";
    if (currentReportType === 'pdf') {
        form.action = "{{ route('agency.sales.report.pdf') }}";
    } else {
        form.action = "{{ route('agency.sales.report.excel') }}";
    }
    form.submit();
    closeFieldsModal();
}
</script>
</x-app-layout> 