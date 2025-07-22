<div class="space-y-6">
    <!-- العنوان الرئيسي -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            التزامات وقيود الموظف
        </h2>
    </div>

    <!-- محتوى الالتزامات للموظف الحالي -->
    @if($employee->obligations->isEmpty())
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <p class="text-gray-500 text-sm">ليس لديك أي التزامات أو قيود حالياً.</p>
        </div>
    @else
        <!-- شبكة البطاقات لكل التزام -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($employee->obligations as $obl)
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-semibold" style="color: rgb(var(--primary-700));">
                                {{ $obl->title }}
                            </h3>
                            @if($obl->for_all)
                                <span class="inline-block px-2 py-1 text-[10px] font-semibold bg-green-100 text-green-800 rounded">
                                    لكل الموظفين
                                </span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mb-3">
                            @if($obl->start_date)
                                <span>من {{ \Illuminate\Support\Carbon::parse($obl->start_date)->format('Y‑m‑d') }}</span>
                            @endif
                            @if($obl->end_date)
                                <span class="ml-2">إلى {{ \Illuminate\Support\Carbon::parse($obl->end_date)->format('Y‑m‑d') }}</span>
                            @endif
                        </div>
                        @if($obl->description)
                            <div class="text-sm text-gray-700 leading-relaxed">
                                {!! nl2br(e($obl->description)) !!}
                            </div>
                        @endif
                    </div>
                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 text-right">
                        <span class="text-xs text-gray-500">
                            آخر تحديث: {{ \Illuminate\Support\Carbon::parse($obl->updated_at)->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
