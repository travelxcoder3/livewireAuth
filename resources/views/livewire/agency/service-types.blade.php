<div class="space-y-6">
    <!-- العنوان والرسائل -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة أنواع الخدمات
        </h2>

        @if(session('message'))
            <div class="rounded-md px-4 py-2 text-center shadow" style="background-color: rgba(var(--primary-100), 0.5); color: rgb(var(--primary-700));">
                {{ session('message') }}
            </div>
        @endif
    </div>

    <!-- محتوى الصفحة -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" style="color: rgb(var(--primary-700));">قائمة أنواع الخدمات</h2>

            @can('service_types.create')
            <button wire:click="showAddModal"
                    class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                + إضافة نوع خدمة
            </button>
            @endcan
        </div>

        <!-- جدول العرض -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-2 py-1">#</th>
                        <th class="px-2 py-1">اسم نوع الخدمة</th>
                        <th class="px-2 py-1">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($serviceTypes as $type)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1 whitespace-nowrap">{{ $loop->iteration }}</td>
                            <td class="px-2 py-1 font-medium">{{ $type->name }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                @can('service_types.edit')
                                <button wire:click="showEditModal({{ $type->id }})"
                                        class="font-medium text-xs mx-1" style="color: rgb(var(--primary-600));">
                                    تعديل
                                </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-gray-400">لا توجد أنواع خدمات مسجلة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- النافذة المنبثقة -->
    @if($showModal)
        <div class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 relative transform transition-all duration-300">
                <button wire:click="$set('showModal', false)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $editMode ? 'تعديل نوع الخدمة' : 'إضافة نوع خدمة جديدة' }}
                </h3>

                <form wire:submit.prevent="saveServiceType" class="space-y-4 text-sm">
                    @php
                        $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                        $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                        $containerClass = 'relative mt-1';
                    @endphp

                    <!-- اسم نوع الخدمة -->
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model.defer="name" class="{{ $fieldClass }}" placeholder="اسم نوع الخدمة" />
                        <label class="{{ $labelClass }}">اسم نوع الخدمة</label>
                        @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

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

    <!-- Toast -->
    @if(session()->has('message'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 2000)"
             x-show="show"
             x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
             style="background-color: rgb(var(--primary-500));">
            {{ session('message') }}
        </div>
    @endif

    <!-- تحسينات CSS -->
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

        button[wire\:click="showAddModal"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        button[wire\:click="showAddModal"]:active {
            transform: translateY(0);
        }

        form button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        form button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>
