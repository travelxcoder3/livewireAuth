<div class="space-y-6">
    <!-- العنوان الرئيسي -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            السياسات المعتمدة في الوكالة
        </h2>
    </div>

    <!-- عرض السياسات -->
    @if($policies->isEmpty())
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <p class="text-gray-500 text-sm">لا توجد سياسات مضافة حتى الآن.</p>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($policies as $policy)
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-lg font-semibold" style="color: rgb(var(--primary-700));">
                                {{ $policy->title }}
                            </h3>
                            <span class="text-xs px-2 py-1 rounded-full" style="background-color: rgba(var(--primary-100), 0.5); color: rgb(var(--primary-700));">
                                السياسة #{{ $loop->iteration }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-700 leading-relaxed border-t pt-3 border-gray-100">
                            {!! nl2br(e($policy->content)) !!}
                        </div>
                    </div>
                    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <span class="text-xs text-gray-500">
                            آخر تحديث: {{ $policy->updated_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>