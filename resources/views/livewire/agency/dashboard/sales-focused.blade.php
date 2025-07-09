<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-black mb-4">لوحة تحكم إدارة المبيعات</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-blue-50 rounded-lg p-4 flex flex-col items-center">
            <span class="text-3xl font-bold text-blue-700 mb-2">
                {{ \App\Models\Sale::where('agency_id', Auth::user()->agency_id)->count() }}
            </span>
            <span class="text-gray-900">إجمالي المبيعات</span>
        </div>
        <div class="bg-green-50 rounded-lg p-4 flex flex-col items-center">
            <span class="text-3xl font-bold text-green-700 mb-2">
                {{ \App\Models\Sale::where('agency_id', Auth::user()->agency_id)->count() }}
            </span>
            <span class="text-gray-900">إجمالي المبيعات (بدون تصنيف حالة)</span>
        </div>
    </div>
    <div class="flex flex-wrap gap-4 justify-center mt-6">
        @can('sales.view')
            <a href="{{ route('agency.sales.index') }}" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-primary); color: var(--theme-on-primary);">عرض جميع المبيعات</a>
        @endcan
        @can('sales.create')
            <a href="{{ route('agency.sales.index') }}#add" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-success); color: var(--theme-on-success);">إضافة عملية بيع</a>
        @endcan
        @can('sales.report')
            <a href="{{ route('agency.sales.report.pdf') }}" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-info); color: var(--theme-on-info);">تقرير PDF</a>
            <a href="{{ route('agency.sales.report.excel') }}" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-warning); color: var(--theme-on-warning);">تقرير Excel</a>
        @endcan
    </div>
</div> 