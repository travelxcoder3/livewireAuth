<div class="space-y-6">
    <!-- العنوان + زر الإضافة -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            السياسات الخاصة بالوكالة
        </h2>

        <button wire:click="create"
            class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
            + إضافة سياسة جديدة
        </button>
    </div>

    <!-- جدول عرض السياسات -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        @php
            use App\Tables\PolicyTable;
            $columns = PolicyTable::columns();
            // تجهيز البيانات مع index
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

    <!-- النافذة المنبثقة لإضافة / تعديل سياسة -->
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
                    @php
                        $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                        $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                        $containerClass = 'relative mt-1';
                    @endphp

                    <!-- عنوان السياسة -->
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="title" class="{{ $fieldClass }}" placeholder="عنوان السياسة" />
                        <label class="{{ $labelClass }}">عنوان السياسة</label>
                        @error('title') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- محتوى السياسة -->
                    <div class="{{ $containerClass }}">
                        <textarea wire:model.defer="content" rows="5" class="{{ $fieldClass }}" placeholder="محتوى السياسة"></textarea>
                        <label class="{{ $labelClass }}">محتوى السياسة</label>
                        @error('content') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- أزرار الحفظ والإلغاء -->
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                            إلغاء
                        </button>
                        <button wire:click="save"
                                class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            {{ $isEdit ? 'تحديث' : 'إضافة' }}
                        </button>
                    </div>
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
                    <button wire:click="delete"
                        class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                        حذف
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- رسالة نجاح -->
    @if(session()->has('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 2000)"
             x-show="show"
             x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
             style="background-color: rgb(var(--primary-500));">
            {{ session('success') }}
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