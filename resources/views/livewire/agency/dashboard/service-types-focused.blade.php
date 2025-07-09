<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-black mb-4">لوحة تحكم إدارة الخدمات</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-blue-50 rounded-lg p-4 flex flex-col items-center">
            <span class="text-3xl font-bold text-blue-700 mb-2">
                {{ \App\Models\ServiceType::where('agency_id', Auth::user()->agency_id)->count() }}
            </span>
            <span class="text-gray-900">إجمالي أنواع الخدمات</span>
        </div>
        <div class="bg-green-50 rounded-lg p-4 flex flex-col items-center">
            <span class="text-3xl font-bold text-green-700 mb-2">
                {{ \App\Models\ServiceType::where('agency_id', Auth::user()->agency_id)->where('is_active', 1)->count() }}
            </span>
            <span class="text-gray-900">أنواع الخدمات النشطة</span>
        </div>
    </div>
    <div class="flex flex-wrap gap-4 justify-center mt-6">
        @can('service_types.view')
            <a href="{{ route('agency.service_types') }}" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-primary); color: var(--theme-on-primary);">عرض جميع أنواع الخدمات</a>
        @endcan
        @can('service_types.create')
            <a href="{{ route('agency.service_types') }}#add" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-success); color: var(--theme-on-success);">إضافة أنواع خدمة جديدة</a>
        @endcan
    </div>
</div> 