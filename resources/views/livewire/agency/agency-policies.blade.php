<div class="space-y-6">
    <!-- العنوان + زر الإضافة -->
    <div class="flex justify-between items-center pb-2 border-b" style="border-color: rgba(var(--primary-200), 0.5);">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700));">
            السياسات الخاصة بالوكالة
        </h2>
   <x-toast />
        <x-primary-button wire:click="create" icon="+">
            إضافة سياسة جديدة
        </x-primary-button>
    </div>

    <!-- جدول عرض السياسات -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        @php
            use App\Tables\PolicyTable;
            $columns = PolicyTable::columns();
            $rows = $policies->map(function($policy, $i) {
                $policy->index = $i + 1;
                $policy->content = \Illuminate\Support\Str::limit(strip_tags($policy->content), 80);
                return $policy;
            });
        @endphp
        
        @if($rows->isEmpty())
            <p class="text-gray-500 text-sm p-4">لا توجد سياسات مضافة بعد.</p>
        @else
            <x-data-table :rows="$rows" :columns="$columns" />
        @endif
    </div>

    <!-- نافذة إضافة/تعديل السياسة -->
    @if($showModal)
        <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-24 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
                <button wire:click="$set('showModal', false)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $isEdit ? 'تعديل السياسة' : 'إضافة سياسة جديدة' }}
                </h3>

                <div class="space-y-4 text-sm">
                    <!-- عنوان السياسة -->
                  <x-input-field 
                        wireModel="title"
                        label="عنوان السياسة"
                        placeholder="عنوان السياسة"
                        errorName="title"
                    />


                    <!-- محتوى السياسة -->
                    <div class="relative mt-1">
                        <textarea wire:model.defer="content" rows="5"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs"
                            placeholder="محتوى السياسة"></textarea>
                        @error('content') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- أزرار الحفظ والإلغاء -->
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="$set('showModal', false)"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                        إلغاء
                    </button>
                    
                    <x-primary-button wire:click="save">
                        {{ $isEdit ? 'تحديث' : 'إضافة' }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif

    <!-- نافذة تأكيد الحذف -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
                <button wire:click="$set('showDeleteModal', false)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    تأكيد الحذف
                </h3>

                <p class="text-sm text-gray-600 mb-6 text-center">هل أنت متأكد من رغبتك في حذف هذه السياسة؟ لا يمكن التراجع عن هذا الإجراء.</p>

                <div class="flex justify-center gap-3 pt-4">
                <button type="button" wire:click="$set('showDeleteModal', false)"
        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
    إلغاء
</button>
                    
                    <x-primary-button wire:click="delete">
                        حذف
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif

 

    <!-- CSS مخصص -->
    <style>
        .peer:placeholder-shown + label {
            top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .peer:not(:placeholder-shown) + label,
        .peer:focus + label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: rgb(var(--primary-600));
        }
    </style>
</div>