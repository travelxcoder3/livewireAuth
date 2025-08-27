<div wire:poll.2s class="space-y-6 p-6 bg-gray-50 min-h-screen">

    <!-- الصندوق الرئيسي -->
    <div class="bg-white rounded-xl shadow-md p-5 space-y-4">
        <!-- عنوان الصفحة -->
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-[rgb(var(--primary-700))]">
                طلبات الموافقة
            </h2>
        </div>

        <!-- محتوى الطلبات -->
        @if($requests->count())
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm text-right bg-white divide-y divide-gray-100">
                    <thead class="bg-gray-100 text-[rgb(var(--primary-700))] font-semibold">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">#</th>
                            <th class="px-4 py-3 whitespace-nowrap">العنصر</th>
                            <th class="px-4 py-3 whitespace-nowrap">النوع</th>
                            <th class="px-4 py-3 whitespace-nowrap">الوكالة/الفرع</th>
                            <th class="px-4 py-3 whitespace-nowrap">طُلب بواسطة</th>
                            <th class="px-4 py-3 whitespace-nowrap">الحالة</th>
                            <th class="px-4 py-3 whitespace-nowrap">ملاحظات</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($requests as $req)
                            @php
                                $isSale = $req->model_type === \App\Models\Sale::class;

                                if ($isSale) {
                                    $sale = \App\Models\Sale::find($req->model_id);
                                    $itemName = $sale
                                        ? ('عملية #' . $sale->id . ' | مرجع: ' . ($sale->reference ?? '-'))
                                        : '-';
                                    $typeLabel = 'تعديل عملية بيع';
                                } else {
                                    $provider = \App\Models\Provider::find($req->model_id);
                                    $itemName = $provider?->name ?? '-';
                                    $typeLabel = 'مزود';
                                }
                            @endphp

                            <tr>
                                <td class="px-4 py-3">{{ $req->id }}</td>

                                <!-- العنصر -->
                                <td class="px-4 py-3">
                                    {{ $itemName }}
                                </td>

                                <!-- النوع -->
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        {{ $isSale ? 'bg-blue-100 text-blue-700' : 'bg-teal-100 text-teal-700' }}">
                                        {{ $typeLabel }}
                                    </span>
                                </td>

                                <!-- الوكالة -->
                                <td class="px-4 py-3">{{ $req->agency?->name ?? '-' }}</td>

                                <!-- طُلب بواسطة -->
                                <td class="px-4 py-3">{{ $req->requestedBy?->name ?? '-' }}</td>

                                <!-- الحالة -->
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        {{ match($req->status) {
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                        'approved' => 'bg-green-100 text-green-700',
                        'rejected' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-500',
                                        } }}">
                                        {{ match($req->status) {
                                            'pending' => 'قيد الانتظار',
                                            'approved' => 'موافَق عليه',
                                            'rejected' => 'مرفوض',
                                            default => 'غير معروف',
                                        } }}
                                    </span>
                                </td>

                                <!-- ملاحظات -->
                                <td class="px-4 py-3">{{ $req->notes ?? '-' }}</td>

                                <!-- الإجراءات -->
                                <td class="px-4 py-3 text-center">
                                    @if($req->status === 'pending')
                                        <div class="flex justify-center items-center gap-2">
                                            <button wire:click="approve({{ $req->id }})"
                                                class="px-4 py-1.5 rounded-xl text-white text-sm font-semibold shadow
                                                       bg-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-600))] transition">
                                                موافقة
                                            </button>
                                            <button wire:click="reject({{ $req->id }})"
                                                class="px-4 py-1.5 rounded-xl text-white text-sm font-semibold shadow
                                                       bg-red-500 hover:bg-red-600 transition">
                                                رفض
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">تمت المعالجة</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- رسالة لا توجد بيانات -->
            <div class="text-center text-gray-500 py-12 text-sm">
                لا توجد طلبات موافقة معلقة حالياً.
            </div>
        @endif
    </div>
</div>
