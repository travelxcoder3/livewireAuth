@php
    use App\Services\ThemeService;
    use App\Tables\ProviderTable;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $user = auth()->user();
    $columns = ProviderTable::columns($user);
@endphp

{{-- رسائل الجلسة للتصحيح --}}
@if (session('message'))
    <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 6px; margin-bottom: 10px;">{{ session('message') }}</div>
@endif
@if (session('error'))
    <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 10px;">{{ session('error') }}</div>
@endif
@if (session('debug'))
    <div style="background: #fef9c3; color: #92400e; padding: 10px; border-radius: 6px; margin-bottom: 10px;">{{ session('debug') }}</div>
@endif

<div class="space-y-6" wire:poll.3s>
    <!-- العنوان والرسائل -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة المزودين
        </h2>

        @if(session('message'))
            <div class="bg-white rounded-md px-4 py-2 text-center shadow text-sm"
                 style="color: rgb(var(--primary-700)); border: 1px solid rgba(var(--primary-200), 0.5);">
                {{ session('message') }}
            </div>
        @endif
    </div>

    <!-- نموذج الإضافة -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" style="color: rgb(var(--primary-700));">قائمة المزودين</h2>

            @if($user->can('providers.create'))
            <button wire:click="showAddModal"
                    class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                + إضافة مزود جديد
            </button>
            @endif
        </div>

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

                    <!-- اسم المزود -->
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="name" class="{{ $fieldClass }}" placeholder="اسم المزود" />
                        <label class="{{ $labelClass }}">اسم المزود</label>
                        @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- نوع المزود -->
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="type" class="{{ $fieldClass }}" placeholder="نوع المزود (اختياري)" />
                        <label class="{{ $labelClass }}">نوع المزود</label>
                        @error('type') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- معلومات التواصل -->
                    <div class="{{ $containerClass }}">
                        <textarea wire:model.defer="contact_info" rows="2" class="{{ $fieldClass }}" placeholder="معلومات التواصل"></textarea>
                        <label class="{{ $labelClass }}">معلومات التواصل</label>
                        @error('contact_info') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <!-- نوع الخدمة -->
                    <x-select-field
                        label="نوع الخدمة"
                        wireModel="service_item_id"
                        :options="$services->pluck('label', 'id')->toArray()"
                        placeholder="اسم الخدمة"
                        containerClass="relative mt-1"
                        errorName="service_item_id"
                    />


                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                            إلغاء
                        </button>
                        <button type="submit"
                                class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            {{ $editMode ? 'تحديث' : 'إضافة' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- تنبيه عائم -->
    @if(session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
             style="background-color: rgb(var(--primary-500));">
            {{ session('message') }}
        </div>
    @endif

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
