@php
    use App\Services\ThemeService;
    use App\Tables\ProviderTable;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $user = auth()->user();
    $columns = ProviderTable::columns($user);
@endphp



<div class="space-y-6" wire:poll.3s>
    <!-- العنوان والرسائل -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة المزودين
        </h2>

           @if($user->can('providers.create'))
                <x-primary-button wire:click="showAddModal" padding="px-4 py-2">
                     إضافة مزود جديد
                </x-primary-button>

            @endif
            
       <x-toast />
    </div>

    <!-- نموذج الإضافة -->
    <div class="bg-white rounded-xl shadow-md p-4">
      

        <!-- ✅ فلاتر البحث داخل نفس الكارد -->
<div class="grid md:grid-cols-4 gap-3 mb-4 text-sm">
    <x-input-field
        wireModel="search"
        label="بحث بالاسم"
        placeholder="ابحث باسم المزود"
    />

    <x-input-field
        wireModel="typeFilter"
        label="نوع المزود"
        placeholder="ابحث بنوع المزود"
    />

    <x-select-field
        wireModel="serviceFilter"
        label="نوع الخدمة"
        :options="$services->pluck('label', 'id')->toArray()"
        placeholder="الكل"
    />

    <div class="flex items-end justify-end">
        <button type="button" wire:click="resetFilters"
            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm w-full sm:w-auto">
            تنظيف الفلاتر
        </button>
    </div>
</div>


        <!-- جدول المزودين -->
       <!-- جدول المزودين -->
        <div class="overflow-x-auto">
            <x-data-table :rows="$providers" :columns="$columns" />
        </div>

    </div>

    <!-- النافذة المنبثقة -->
    @if($showModal)
    <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-24 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 relative transform transition-all duration-300">
                <button wire:click="$set('showModal', false)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $editMode ? 'تعديل المزود' : 'إضافة مزود جديد' }}
                </h3>

                <form wire:submit.prevent="saveProvider" class="space-y-4 text-sm">
                    @php
                        $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                        $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                        $containerClass = 'relative mt-1';
                    @endphp

                    <x-input-field
                        name="name"
                        label="اسم المزود"
                        placeholder="اسم المزود"
                        wireModel="name"
                        errorName="name"
                        containerClass="{{ $containerClass }}"
                        fieldClass="{{ $fieldClass }}"
                    />


                    <!-- نوع المزود -->
                   <x-input-field
                        name="type"
                        label="نوع المزود"
                        placeholder="نوع المزود (اختياري)"
                        wireModel="type"
                        
                    />

                    <!-- معلومات التواصل -->
                    <x-input-field
                        name="type"
                        label=" رقم التواصل"
                        placeholder="  رقم التواصل"
                        wireModel="contact_info"
                        
                    />
                    <!-- نوع الخدمة -->
<x-multi-check-dropdown
    label="أنواع الخدمة"
    wireModel="service_item_ids"
    :options="$services->map(fn($s)=> ['id'=>(string)$s->id,'label'=>$s->label])->values()->toArray()"
/>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                            إلغاء
                        </button>
                    <x-primary-button
    type="button"
    padding="px-4 py-2"
    x-data
    @click="window.dispatchEvent(new CustomEvent('confirm:open', { detail: {
        title:       '{{ $editMode ? 'تأكيد تحديث المزود' : 'تأكيد إضافة المزود' }}',
        message:     '{{ $editMode ? 'سيتم تحديث بيانات المزود. متابعة؟' : 'سيتم إضافة مزود جديد بالبيانات المدخلة. متابعة؟' }}',
        icon:        '{{ $editMode ? 'info' : 'check' }}',
        confirmText: '{{ $editMode ? 'تحديث' : 'إضافة' }}',
        cancelText:  'إلغاء',
        onConfirm:   'saveProvider',
        payload:     null
    }}))"
>
    {{ $editMode ? 'تحديث' : 'إضافة' }}
</x-primary-button>


                    </div>
                </form>
            </div>
        </div>
    @endif

<x-confirm-dialog />
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

        button[wire\:click="showAddModal"]:hover,
        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        button[wire\:click="showAddModal"]:active,
        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>


</div>
