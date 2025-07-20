@php
    use App\Services\ThemeService;

    $themeName = ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp
<div>
    <div class="space-y-6">
        <!-- رأس الصفحة -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-black">إدارة القوائم</h2>
            <button wire:click="$set('showRequestsModal', true)"
                class="text-white font-bold px-4 py-2 rounded-md text-sm transition duration-300 hover:shadow-lg"
                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                طلبات القوائم
            </button>
        </div>

        <!-- قسم إضافة قائمة جديدة (للسوبر أدمن فقط) -->
        @if (auth()->user()?->hasRole('super-admin'))
            <div class="bg-white p-6 rounded-xl shadow-md">
                <form wire:submit.prevent="saveList" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <input type="text" wire:model.defer="newListName"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none bg-white text-sm"
                            placeholder="إضافة قائمة جديدة"
                            style="border-color: rgb({{ $colors['primary-500'] }}); box-shadow: 0 0 0 2px rgba({{ $colors['primary-500'] }}, 0.2);">

                        @error('newListName')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit"
                        class="text-white font-bold px-6 py-2 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}) 0%, rgb({{ $colors['primary-600'] }}) 100%);">
                        حفظ
                    </button>
                </form>
            </div>
        @endif

        <!-- رسائل النظام -->
        @if (session()->has('success'))
            <div
                class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded-lg text-sm shadow text-center">
                {{ session('success') }}
            </div>
        @endif

        <!-- عرض القوائم -->
        @foreach ($lists as $list)
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <!-- رأس القائمة مع خيارات التحكم -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-black">{{ $list->name }}</h3>
                    <div class="flex items-center gap-2">
                        <!-- خيارات التعديل والحذف للقائمة -->
                        @if ($this->canEditList($list))
                            @if ($editingListId === $list->id)
                                <form wire:submit.prevent="updateList" class="flex items-center gap-2">
                                    <input type="text" wire:model.defer="newListName"
                                        class="text-xs border rounded-lg px-3 py-1 focus:ring focus:ring-primary-300 w-40">
                                    <button type="submit"
                                        class="bg-green-500 text-white px-3 py-1 rounded-xl text-xs font-medium hover:bg-green-600 transition shadow-sm">
                                        حفظ
                                    </button>
                                    <button type="button" wire:click="$set('editingListId', null)"
                                        class="border border-gray-400 hover:bg-gray-100 px-3 py-1 rounded-xl text-xs font-medium text-gray-600 transition shadow-sm">
                                        إلغاء
                                    </button>
                                </form>
                            @else
                                <button wire:click="editList({{ $list->id }})"
                                    class="text-primary-700 border border-primary-600 hover:bg-primary-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                    تعديل
                                </button>
                                <button wire:click="deleteList({{ $list->id }})"
                                    onclick="return confirmDeleteList({{ $list->id }})"
                                    class="text-red-600 border border-red-500 hover:bg-red-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                    حذف
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- محتوى القائمة (يظهر عند التوسيع) -->
                @if (in_array($list->id, $expandedLists))
                    @unless (auth()->user()->hasRole('super-admin'))
                        <!-- عرض البنود الرئيسية -->
                        @foreach ($list->items as $item)
                            {{-- ... نفس الكود بدون تعديل داخلي ... --}}
                        @endforeach

                        <!-- نموذج إضافة بند رئيسي جديد -->
                        <div class="flex items-center gap-2 ml-4 mt-2">
                            <input type="text" wire:model.defer="itemLabel.{{ $list->id }}" placeholder="اسم البند"
                                class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm">
                            <button wire:click="addItem({{ $list->id }})"
                                class="text-white font-bold px-4 py-1.5 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-xs"
                                style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}) 0%, rgb({{ $colors['primary-600'] }}) 100%);">
                                + إضافة بند
                            </button>
                        </div>
                        @error("itemLabel.$list->id")
                            <span class="text-red-500 text-xs ml-8">{{ $message }}</span>
                        @enderror
                    @endunless
                @endif

            </div>
        @endforeach
        <!-- طلبات القوائم -->
        @if ($showRequestsModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm"
                style="background-color: rgba(0,0,0,0.4);">
                <div
                    class="bg-white w-full max-w-3xl rounded-lg shadow-xl overflow-hidden transform scale-100 transition-all duration-300">
                    <div class="p-6">
                        @if (session()->has('success'))
                            <div class="text-green-600 text-sm mb-3">
                                {{ session('success') }}
                            </div>
                        @elseif (session()->has('error'))
                            <div class="text-red-600 text-sm mb-3">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="flex justify-between items-center border-b pb-2 mb-4">
                            <h3 class="text-lg font-bold text-black">طلبات إنشاء القوائم</h3>
                            <button wire:click="$set('showRequestsModal', false)"
                                class="text-gray-500 hover:text-black text-xl">&times;</button>
                        </div>

                        @php
                            $requests = \App\Models\DynamicList::with('agency')
                                ->where('is_requested', true)
                                ->whereNull('is_approved')
                                ->orderByDesc('created_at')
                                ->get();
                        @endphp

                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            @forelse ($requests as $list)
                                <div class="border rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <!-- اسم القائمة -->
                                            <div class="font-bold">{{ $list->name }}</div>

                                            <!-- معلومات الوكالة والتاريخ -->
                                            <div class="text-sm text-gray-600 mt-1">
                                                من وكالة: <span
                                                    class="text-primary-600">{{ $list->agency->name ?? 'غير معروف' }}</span><br>
                                                تم الطلب بتاريخ: {{ $list->created_at->format('Y-m-d') }}
                                            </div>

                                            <!-- سبب الطلب: يظهر فقط إذا موجود -->
                                            @if ($list->request_reason)
                                                <div
                                                    class="bg-gray-50 border border-dashed border-gray-300 text-gray-700 text-sm p-3 rounded-md mt-2 shadow-sm">
                                                    <strong class="block mb-1 text-gray-600">📌 سبب الطلب:</strong>
                                                    {{ $list->request_reason }}
                                                </div>
                                            @endif

                                        </div>

                                        <!-- أزرار التحكم -->
                                        <div class="flex gap-2 mt-4">
                                            <!-- زر الموافقة -->
                                            <button wire:click="approve({{ $list->id }})"
                                                class="text-green-600 border border-green-500 hover:bg-green-500 hover:text-white font-bold py-1.5 px-4 rounded-md shadow transition-all duration-200">
                                                موافقة
                                            </button>

                                            <!-- زر الرفض -->
                                            <button wire:click="reject({{ $list->id }})"
                                                class="text-red-600 border border-red-500 hover:bg-red-500 hover:text-white font-bold py-1.5 px-4 rounded-md shadow transition-all duration-200">
                                                رفض
                                            </button>
                                        </div>


                                    </div>
                                </div>
                            @empty
                                <div class="text-gray-500 text-sm text-center">لا توجد طلبات حالية.</div>
                            @endforelse
                        </div>

                    </div>
                </div>
            </div>
        @endif


    </div>

    @script
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                /**
                 * تأكيد حذف القائمة مع جميع بنودها
                 * @param {number} listId - معرّف القائمة المراد حذفها
                 */
                window.confirmDeleteList = function(listId) {
                    Swal.fire({
                        title: 'هل أنت متأكد؟',
                        text: "سيتم حذف القائمة وجميع بنودها!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'نعم، احذف القائمة',
                        cancelButtonText: 'إلغاء',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('deleteList', listId)
                                .then(() => {
                                    Swal.fire('تم الحذف!', 'تم حذف القائمة بنجاح.', 'success');
                                });
                        }
                    });
                };

                /**
                 * تأكيد حذف البند الرئيسي مع جميع بنوده الفرعية
                 * @param {number} itemId - معرّف البند المراد حذفه
                 */
                window.confirmDeleteItem = function(itemId) {
                    Swal.fire({
                        title: 'هل أنت متأكد؟',
                        text: "سيتم حذف البند وجميع بنوده الفرعية!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'نعم، احذف البند',
                        cancelButtonText: 'إلغاء',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('deleteItem', itemId)
                                .then(() => {
                                    Swal.fire('تم الحذف!', 'تم حذف البند بنجاح.', 'success');
                                });
                        }
                    });
                };

                /**
                 * تأكيد حذف البند الفرعي
                 * @param {number} subItemId - معرّف البند الفرعي المراد حذفه
                 */
                window.confirmDeleteSubItem = function(subItemId) {
                    Swal.fire({
                        title: 'هل أنت متأكد؟',
                        text: "سيتم حذف البند الفرعي!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'نعم، احذف البند',
                        cancelButtonText: 'إلغاء',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('deleteSubItem', subItemId)
                                .then(() => {
                                    Swal.fire('تم الحذف!', 'تم حذف البند الفرعي بنجاح.', 'success');
                                });
                        }
                    });
                };

                // إعداد مستمعين لأحداث Livewire لعرض رسائل النجاح
                Livewire.on('list-saved', () => {
                    Swal.fire('تم الحفظ!', 'تم حفظ القائمة بنجاح.', 'success');
                });

                Livewire.on('item-saved', () => {
                    Swal.fire('تم الحفظ!', 'تم حفظ البند بنجاح.', 'success');
                });

                Livewire.on('subitem-saved', () => {
                    Swal.fire('تم الحفظ!', 'تم حفظ البند الفرعي بنجاح.', 'success');
                });
            });
        </script>
    @endscript

    <style>
        input:focus,
        select:focus,
        textarea:focus {
            border-color: rgb({{ $colors['primary-500'] }}) !important;
            box-shadow: 0 0 0 2px rgba({{ $colors['primary-500'] }}, 0.2) !important;
        }

        .peer:placeholder-shown+label {
            top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .peer:not(:placeholder-shown)+label,
        .peer:focus+label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: rgb({{ $colors['primary-500'] }}) !important;
        }

        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba({{ $colors['primary-500'] }}, 0.2);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>

</div>
