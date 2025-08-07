@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $agencyId = Auth::user()->agency_id;
@endphp

<div class="p-4">
      <x-toast />
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-black">قوائم الوكالة</h2>
    </div>

    <!-- القوائم -->
    <div class="space-y-6 bg-gray-50" wire:key="lists-container-{{ $lists->count() }}">
        @foreach ($lists as $list)
            <div class="bg-white rounded-xl shadow-md p-6">
                <!-- عنوان القائمة -->
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-lg font-bold text-black">{{ $list->name }}</h3>
                    
                    <button wire:click="toggleExpand({{ $list->id }})"
                        class="text-gray-500 hover:text-primary-600 transition"
                        aria-label="{{ in_array($list->id, $expandedLists) ? 'طي القائمة' : 'توسيع القائمة' }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 transform transition-transform duration-200"
                            :class="{ 'rotate-180': @js(in_array($list->id, $expandedLists)) }" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
                @if (in_array($list->id, $expandedLists))
                    <!-- إدخال بند رئيسي -->
                    <div class="flex items-center gap-2 mb-6">
                        <input type="text" wire:model.defer="itemLabel.{{ $list->id }}"
                            placeholder="اسم البند الرئيسي"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white peer"
                            style="border-color: rgb(var(--primary-500)); box-shadow: 0 0 0 2px rgba(var(--primary-500), 0.2);">
                            @can('lists.create')
                        <button wire:click="addItem({{ $list->id }})" wire:loading.attr="disabled"
                            class="text-white font-bold px-4 py-2 rounded-md text-xs transition duration-300 hover:shadow-lg whitespace-nowrap"
                            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            <span wire:loading.remove wire:target="addItem({{ $list->id }})">+ إضافة بند
                                رئيسي</span>
                            <span wire:loading wire:target="addItem({{ $list->id }})">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                        </button>
                        @endcan

                    </div>
                    @error("itemLabel.$list->id")
                        <span class="text-red-500 text-xs mb-2 block">{{ $message }}</span>
                    @enderror

                    <!-- عرض البنود -->
                    @foreach ($list->items as $item)
                        <div class="bg-gray-50 rounded px-4 py-2 mb-2 border border-gray-200">
                            <div class="flex justify-between items-center">
                                @if ($editingItemId === $item->id)
                                    <div class="flex items-center gap-2 w-full">
                                        <input type="text" wire:model.defer="editingItemLabel"
                                            class="flex-1 border border-gray-300 rounded px-2 py-1 text-sm">
                                            <button wire:click="updateItem"
                                                class="px-2 py-1 text-xs rounded border bg-white transition
                                                    border-[rgb(var(--primary-500))]
                                                    text-[rgb(var(--primary-600))]
                                                    hover:bg-[rgba(var(--primary-100), 0.3)]">
                                                حفظ
                                            </button>
                                        <button wire:click="cancelEditItem"
                                            class="px-2 py-1 text-xs rounded border border-gray-400 text-gray-700 bg-white hover:bg-gray-100 transition mr-2">
                                            إلغاء
                                        </button>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-800 font-medium">{{ $item->label }}</span>
                                @endif

                                @if (!$list->is_system || $item->created_by_agency)
                                    <div class="flex gap-2">
                                    @can('lists.edit')
                                    <button wire:click="startEditItem({{ $item->id }})"
                                            class="px-2 py-1 text-xs rounded border bg-white transition
                                                border-[rgb(var(--primary-500))]
                                                text-[rgb(var(--primary-600))]
                                                hover:bg-[rgba(var(--primary-100), 0.3)]"
                                            >
                                            تعديل
                                        </button>
                                        @endcan
                                        @can('lists.delete')
                                        <button wire:click="deleteItem({{ $item->id }})"
                                            class="px-2 py-1 text-xs rounded border border-red-500 text-red-600 bg-white hover:bg-red-50 transition">
                                            حذف
                                        </button>
                                        @endcan
                                    </div>
                                @endif
                            </div>

                            <!-- البنود الفرعية -->
                            @foreach ($item->subItems as $sub)
                                <div class="ml-6 mt-1 flex justify-between text-xs text-gray-700">
                                    @if ($editingSubItemId === $sub->id)
                                        <div class="flex items-center gap-1 w-full">
                                            <input type="text" wire:model.defer="editingSubItemLabel"
                                                class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs">
                                                <button wire:click="updateSubItem"
                                                    class="px-2 py-1 text-xs rounded border bg-white transition
                                                        border-[rgb(var(--primary-500))]
                                                        text-[rgb(var(--primary-600))]
                                                        hover:bg-[rgba(var(--primary-100), 0.3)]">
                                                    حفظ
                                                </button>
                                            <button wire:click="cancelEditSubItem"
                                                class="px-2 py-1 text-xs rounded border border-gray-400 text-gray-700 bg-white hover:bg-gray-100 transition mr-2">
                                                إلغاء
                                            </button>
                                        </div>
                                    @else
                                        <span>↳ {{ $sub->label }}</span>
                                    @endif
                                    @if (!$list->is_system || $sub->created_by_agency)
                                        <div class="flex gap-1">
                                        @can('lists.edit')
                                        <button wire:click="startEditSubItem({{ $sub->id }})"
                                                class="px-2 py-1 text-xs rounded border bg-white transition
                                                    border-[rgb(var(--primary-500))]
                                                    text-[rgb(var(--primary-600))]
                                                    hover:bg-[rgba(var(--primary-100), 0.3)]"
                                                >
                                                تعديل
                                            </button>
                                            @endcan
                                            @can('lists.delete')
                                            <button wire:click="deleteSubItem({{ $sub->id }})"
                                                class="px-2 py-1 text-xs rounded border border-red-500 text-red-600 bg-white hover:bg-red-50 transition">
                                                حذف
                                            </button>
                                            @endcan
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <!-- إدخال بند فرعي -->
                            @if ($item->created_by_agency === $agencyId)
                                <div class="flex items-center gap-2 mt-2 ml-6">
                                    <input type="text" wire:model.defer="subItemLabel.{{ $item->id }}"
                                        placeholder="اسم البند الفرعي"
                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-1 text-sm focus:outline-none bg-white"
                                        style="border-color: rgb(var(--primary-500)); box-shadow: 0 0 0 1px rgba(var(--primary-500), 0.2);">
                                        @can('lists.create')
                                    <button wire:click="addSubItem({{ $item->id }})" wire:loading.attr="disabled"
                                        class="text-white font-bold px-3 py-1 rounded-md text-xs transition duration-300 hover:shadow"
                                        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                                        إضافة بند فرعي
                                    </button>
                                    @endcan
                                </div>
                                @error("subItemLabel.$item->id")
                                    <span class="text-red-500 text-xs ml-6">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach

    </div>
    <!-- Theme Styles -->
    <style>
        input:focus,
        select:focus,
        textarea:focus {
            border-color: rgb(var(--primary-500)) !important;
            box-shadow: 0 0 0 2px rgba(var(--primary-500), 0.2) !important;
        }

        button[type="submit"],
        button[style*="gradient"] {
            background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%) !important;
            color: #fff;
        }

        button[type="submit"]:hover,
        button[style*="gradient"]:hover {
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        .peer:placeholder-shown+label {
            display: none;
        }

        .peer:not(:placeholder-shown)+label,
        .peer:focus+label {
            display: block;
            color: rgb({{ $colors['primary-500'] }}) !important;
        }

        button[style*="gradient"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba({{ $colors['primary-500'] }}, 0.2);
        }

        button[style*="gradient"]:active {
            transform: translateY(0);
        }
    </style>
</div>
