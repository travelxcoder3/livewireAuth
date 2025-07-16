<div>
    @can('approvals.view')
        <div class="space-y-6">
            <h2 class="text-xl font-bold mb-4">طلبات الموافقة على المزودين</h2>
            @if($requests->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-xl shadow">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700">
                                <th class="px-4 py-2">#</th>
                                <th class="px-4 py-2">اسم المزود</th>
                                <th class="px-4 py-2">اسم الوكالة/الفرع</th>
                                <th class="px-4 py-2">تم الطلب بواسطة</th>
                                <th class="px-4 py-2">الحالة</th>
                                <th class="px-4 py-2">ملاحظات</th>
                                <th class="px-4 py-2">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $req)
                                @php
                                    $provider = \App\Models\Provider::find($req->model_id);
                                @endphp
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $req->id }}</td>
                                    <td class="px-4 py-2">{{ $provider?->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $req->agency?->name ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $req->requestedBy?->name ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded text-xs {{ $req->status == 'pending' ? 'bg-yellow-100 text-yellow-700' : ($req->status == 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $req->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $req->notes ?? '-' }}</td>
                                    <td class="px-4 py-2 flex gap-2">
                                        @can('approvals.manage')
                                            @if($req->status == 'pending')
                                                <button wire:click="approve({{ $req->id }})" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded">موافقة</button>
                                                <button wire:click="reject({{ $req->id }})" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">رفض</button>
                                            @else
                                                <span class="text-gray-400">تمت المعالجة</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">ليس لديك صلاحية</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 py-10">لا توجد طلبات موافقة معلقة حالياً.</div>
            @endif
        </div>
    @else
        <div class="text-center text-gray-500 py-10">ليس لديك صلاحية لعرض هذه الصفحة.</div>
    @endcan
</div> 