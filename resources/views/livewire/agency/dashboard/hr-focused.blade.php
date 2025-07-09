<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-black mb-4">لوحة تحكم إدارة الموارد البشرية</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-blue-50 rounded-lg p-4 flex flex-col items-center">
            <span class="text-3xl font-bold text-blue-700 mb-2">
                {{ \App\Models\User::where('agency_id', Auth::user()->agency_id)->whereHas('roles', function($q){ $q->where('name', 'employee'); })->count() }}
            </span>
            <span class="text-gray-900">إجمالي الموظفين</span>
        </div>
        <div class="bg-green-50 rounded-lg p-4 flex flex-col items-center">
            <span class="text-3xl font-bold text-green-700 mb-2">
                {{ \App\Models\User::where('agency_id', Auth::user()->agency_id)->where('is_active', 1)->count() }}
            </span>
            <span class="text-gray-900">الموظفون النشطون</span>
        </div>
    </div>
    <div class="flex flex-wrap gap-4 justify-center mt-6">
        @can('employees.view')
            <a href="{{ route('agency.hr.employees.index') }}" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-primary); color: var(--theme-on-primary);">عرض جميع الموظفين</a>
        @endcan
        @can('employees.create')
            <a href="{{ route('agency.hr.employees.index') }}#add" class="px-6 py-3 rounded-lg font-bold shadow" style="background: var(--theme-success); color: var(--theme-on-success);">إضافة موظف جديد</a>
        @endcan
    </div>
</div> 