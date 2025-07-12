@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $user = auth()->user();
@endphp

<div class="space-y-6">
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
            <table class="min-w-full divide-y divide-gray-200 text-xs text-right">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-2 py-1">نوع الخدمة</th>
                        <th class="px-2 py-1">اسم المزود</th>
                        <th class="px-2 py-1">النوع</th>
                        <th class="px-2 py-1">معلومات التواصل</th>
                        <th class="px-2 py-1">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($providers as $provider)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1 text-xs">{{ $provider->service?->label ?? '-' }} </td>
                            <td class="px-2 py-1 font-medium">{{ $provider->name }}</td>
                            <td class="px-2 py-1">
                                @if($provider->type)
                                    <span class="inline-flex px-1.5 py-0.5 text-2xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $provider->type }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-2 py-1 text-xs whitespace-pre-line">{{ $provider->contact_info ?? '-' }}</td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                @if($user->can('providers.edit'))
                                <button wire:click="showEditModal({{ $provider->id }})"
                                        class="font-medium text-xs mx-1"
                                        style="color: rgb(var(--primary-600));">
                                    تعديل
                                </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-400">لا توجد مزودين مسجلين</td>
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
                    <div class="{{ $containerClass }}">
                        <select wire:model.defer="service_item_id" class="{{ $fieldClass }}">
                            <option value="">اختر نوع الخدمة</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->label }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $labelClass }}">نوع الخدمة</label>
                        @error('service_item_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
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
