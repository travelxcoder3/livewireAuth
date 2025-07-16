@php
    use App\Services\ThemeService;

    $themeName = ThemeService::getSystemTheme();
    $colors = ThemeService::getCurrentThemeColors($themeName);
@endphp
<div>
<div class="space-y-6">
    <!-- قسم إضافة قائمة جديدة (للسوبر أدمن فقط) -->
    @if (auth()->user()?->hasRole('super-admin'))
        <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-bold text-center mb-4 text-black">إضافة قائمة جديدة</h2>
            <form wire:submit.prevent="{{ $editingListId ? 'updateList' : 'saveList' }}" class="flex gap-4 items-end">
                <div class="flex-1 relative">
                    <input type="text" wire:model.defer="newListName" 
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none bg-white text-xs peer" 
                           placeholder="اسم القائمة">
                    <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs">
                        اسم القائمة
                    </label>
                    @error('newListName')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
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
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded-lg text-sm shadow text-center">
            {{ session('success') }}
        </div>
    @endif

    <!-- عرض القوائم -->
    @foreach ($lists as $list)
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-black">{{ $list->name }}</h3>
                @if (auth()->user()->hasRole('super-admin'))
                    <div class="flex items-center gap-2">
                        <button wire:click="editList({{ $list->id }})"
                            class="text-primary-700 border border-primary-600 hover:bg-primary-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                            تعديل
                        </button>
                        <button wire:click="deleteList({{ $list->id }})"
                            onclick="return confirm('هل أنت متأكد من حذف القائمة؟')"
                            class="text-red-600 border border-red-500 hover:bg-red-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                            حذف
                        </button>
                    </div>
                @endif
            </div>
            @if (!auth()->user()->hasRole('super-admin'))
                @if (in_array($list->id, $expandedLists))
                    @foreach ($list->items as $item)
                        <div class="bg-gray-50 border rounded-lg p-4 mb-4 ml-4 space-y-3">
                            <!-- رأس البند الرئيسي -->
                            <div class="flex justify-between items-center">
                                @if ($editingItemId === $item->id)
                                    <form wire:submit.prevent="updateItem" class="flex-1">
                                        <input type="text" wire:model.defer="editingItemLabel"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-1 text-xs focus:ring-primary-500">
                                    </form>
                                @else
                                    <button wire:click="toggleItemExpand({{ $item->id }})" class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500 transform transition-transform duration-200"
                                            :class="{ 'rotate-90': @js(in_array($item->id, $expandedItems)) }" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        <span class="text-black font-medium text-sm">{{ $item->label }}</span>
                                    </button>
                                @endif

                                <!-- خيارات التعديل والحذف للبند الرئيسي -->
                                @if ($this->canEditItem($item))
                                    <div class="flex items-center gap-2">
                                        @if ($editingItemId === $item->id)
                                            <button type="submit" wire:click="updateItem"
                                                class="text-green-700 border border-green-600 hover:bg-green-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                                حفظ
                                            </button>
                                            <button type="button" wire:click="$set('editingItemId', null)"
                                                class="text-gray-600 border border-gray-500 hover:bg-gray-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                                إلغاء
                                            </button>
                                        @else
                                            <button wire:click="startEditItem({{ $item->id }})"
                                                class="text-primary-700 border border-primary-600 hover:bg-primary-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                                تعديل
                                            </button>
                                            <button wire:click="deleteItem({{ $item->id }})"
                                                onclick="return confirmDeleteItem({{ $item->id }})"
                                                class="text-red-600 border border-red-500 hover:bg-red-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                                حذف
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- البنود الفرعية (تظهر عند توسيع البند الرئيسي) -->
                            @if(in_array($item->id, $expandedItems) && !auth()->user()->hasRole('super-admin'))
                                @foreach ($item->subItems as $sub)
                                    <div class="flex justify-between items-center gap-2 pl-8 text-xs">
                                        @if ($editingSubItemId === $sub->id)
                                            <form wire:submit.prevent="updateSubItem" class="flex items-center gap-2 w-full">
                                                <input type="text" wire:model.defer="editingSubItemLabel"
                                                    class="flex-1 rounded-lg border px-3 py-1 text-xs focus:ring-primary-500">
                                                <button type="submit"
                                                    class="bg-green-500 text-white px-3 py-1 rounded-xl text-xs font-medium hover:bg-green-600 transition shadow-sm">
                                                    حفظ
                                                </button>
                                                <button type="button" wire:click="$set('editingSubItemId', null)"
                                                    class="border border-gray-400 hover:bg-gray-100 px-3 py-1 rounded-xl text-xs font-medium text-gray-600 transition shadow-sm">
                                                    إلغاء
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-800">{{ $sub->label }}</span>
                                            <!-- خيارات التعديل والحذف للبند الفرعي -->
                                            @if ($this->canEditSub($sub))
                                                <div class="flex items-center gap-2">
                                                    <button wire:click="startEditSubItem({{ $sub->id }})"
                                                        class="text-primary-700 border border-primary-600 hover:bg-primary-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                                        تعديل
                                                    </button>
                                                    <button wire:click="deleteSubItem({{ $sub->id }})"
                                                        onclick="return confirmDeleteSubItem({{ $sub->id }})"
                                                        class="text-red-600 border border-red-500 hover:bg-red-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                                        حذف
                                                    </button>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach

                                <!-- نموذج إضافة بند فرعي جديد -->
                                <div class="flex items-center gap-2 mt-2 pl-8">
                                    <input type="text" wire:model.defer="subItemLabel.{{ $item->id }}"
                                        placeholder="اسم البند الفرعي"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm">
                                    <button wire:click="addSubItem({{ $item->id }})"
                                        class="text-white font-bold px-4 py-1.5 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-xs"
                                        style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}) 0%, rgb({{ $colors['primary-600'] }}) 100%);">
                                        + إضافة بند فرعي
                                    </button>
                                </div>
                                @error("subItemLabel.$item->id")
                                    <span class="text-red-500 text-xs pl-8">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
                    @endforeach

                    <!-- نموذج إضافة بند رئيسي جديد -->
                    @if(!auth()->user()->hasRole('super-admin'))
                    <div class="flex items-center gap-2 ml-4 mt-2">
                        <input type="text" wire:model.defer="itemLabel.{{ $list->id }}" placeholder="اسم البند"
                            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:ring-2 focus:ring-primary-500 focus:outline-none shadow-sm">
                        <button wire:click="addItem({{ $list->id }})"
                            class="text-white font-bold px-4 py-1.5 rounded-xl shadow-md hover:shadow-xl transition duration-300 text-xs"
                            style="background: linear-gradient(to right, rgb({{ $colors['primary-500'] }}) 0%, rgb({{ $colors['primary-600'] }}) 100%);">
                            + إضافة بند
                        </button>
                    </div>
                    @endif
                    @error("itemLabel.$list->id")
                        <span class="text-red-500 text-xs ml-8">{{ $message }}</span>
                    @enderror
                @endif
            @endif
        </div>
    @endforeach
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
    input:focus, select:focus, textarea:focus {
        border-color: rgb({{ $colors['primary-500'] }}) !important;
        box-shadow: 0 0 0 2px rgba({{ $colors['primary-500'] }}, 0.2) !important;
    }

    .peer:placeholder-shown + label {
        top: 0.75rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .peer:not(:placeholder-shown) + label,
    .peer:focus + label {
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