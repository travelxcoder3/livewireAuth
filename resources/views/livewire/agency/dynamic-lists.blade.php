@php
use App\Services\ThemeService;
$themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
$colors = ThemeService::getCurrentThemeColors($themeName);
@endphp

<div>
    <div class="space-y-6 p-4 bg-gray-50" wire:key="lists-container-{{ $lists->count() }}">
        @if (auth()->user()->hasRole('super-admin'))
            <div class="bg-white p-6 rounded-xl shadow-md">
                <h2 class="text-xl font-bold text-center mb-4 text-black">إضافة قائمة جديدة</h2>
                <form wire:submit.prevent="saveList" class="flex gap-4 items-end">
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
        @foreach ($lists as $list)
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h3 class="text-lg font-bold text-black">{{ $list->name }}</h3>
                    @if (auth()->user()->hasRole('agency-admin'))
                        <button wire:click="toggleExpand({{ $list->id }})"
                            class="text-gray-500 hover:text-primary-600 transition"
                            aria-label="{{ in_array($list->id, $expandedLists) ? 'طي القائمة' : 'توسيع القائمة' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform transition-transform duration-200"
                                :class="{ 'rotate-180': @js(in_array($list->id, $expandedLists)) }" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    @endif
                </div>
                @if (auth()->user()->hasRole('agency-admin') && in_array($list->id, $expandedLists))
                    @foreach ($list->items as $item)
                        <div class="bg-gray-50 border rounded-lg p-4 mb-4" wire:key="item-{{ $item->id }}">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-gray-800 font-medium">{{ $item->label }}</span>
                                <div class="flex items-center gap-2">
                                    <button wire:click="startEditItem({{ $item->id }})"
                                        class="text-primary-700 border border-primary-600 hover:bg-primary-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                        تعديل
                                    </button>
                                    <button wire:click="deleteItem({{ $item->id }})"
                                        onclick="return confirm('هل أنت متأكد من حذف البند الرئيسي؟')"
                                        class="text-red-600 border border-red-500 hover:bg-red-50 px-3 py-1 rounded-xl text-xs font-medium transition shadow-sm">
                                        حذف
                                    </button>
                                </div>
                            </div>
                            <ul class="space-y-3">
                                @foreach ($item->subItems as $sub)
                                    <li class="flex justify-between items-center px-2">
                                        @if ($editingSubItemId === $sub->id)
                                            <form wire:submit.prevent="updateSubItem" class="flex items-center gap-2 w-full">
                                                <input type="text" wire:model.defer="editingSubItemLabel"
                                                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white">
                                                <button type="submit"
                                                    class="text-white font-bold px-3 py-1.5 rounded-md text-xs transition"
                                                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                                                    حفظ
                                                </button>
                                                <button type="button" wire:click="$set('editingSubItemId', null)"
                                                    class="border border-gray-400 hover:bg-gray-100 px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 transition">
                                                    إلغاء
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-sm text-gray-600">{{ $sub->label }}</span>
                                            <div class="flex items-center gap-2">
                                                <button wire:click="startEditSubItem({{ $sub->id }})"
                                                    class="text-white font-bold px-3 py-1.5 rounded-md text-xs transition"
                                                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                                                    تعديل
                                                </button>
                                                <button wire:click="deleteSubItem({{ $sub->id }})"
                                                    onclick="return confirm('هل أنت متأكد من الحذف؟')"
                                                    class="text-red-600 border border-red-500 hover:bg-red-50 px-3 py-1.5 rounded-md text-xs font-medium transition">
                                                    حذف
                                                </button>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            <div class="flex items-center gap-2 mt-4 relative">
                                <input type="text" wire:model.defer="subItemLabel.{{ $item->id }}"
                                    placeholder="اسم البند الفرعي"
                                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white peer">
                                <button wire:click="addSubItem({{ $item->id }})" wire:loading.attr="disabled"
                                    class="text-white font-bold px-4 py-2 rounded-md text-xs transition duration-300 hover:shadow-lg whitespace-nowrap"
                                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                                    <span wire:loading.remove wire:target="addSubItem({{ $item->id }})">+ إضافة</span>
                                    <span wire:loading wire:target="addSubItem({{ $item->id }})">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                            @error("subItemLabel.$item->id")
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                    <div class="flex items-center gap-2 mt-4">
                        <input type="text" wire:model.defer="itemLabel.{{ $list->id }}" placeholder="اسم البند الرئيسي"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white">
                        <button wire:click="addItem({{ $list->id }})"
                            class="text-white font-bold px-4 py-2 rounded-md text-xs transition duration-300 hover:shadow-lg whitespace-nowrap"
                            style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            <span wire:loading.remove wire:target="addItem({{ $list->id }})">+ إضافة بند رئيسي</span>
                            <span wire:loading wire:target="addItem({{ $list->id }})">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                        </button>
                    </div>
                    @error("itemLabel.$list->id")
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                @endif
            </div>
        @endforeach
    </div>

    <!-- Theme Styles -->
    <style>
        input:focus, select:focus, textarea:focus {
            border-color: rgb(var(--primary-500)) !important;
            box-shadow: 0 0 0 2px rgba(var(--primary-500), 0.2) !important;
        }
        button[type="submit"], button[style*="gradient"] {
            background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%) !important;
            color: #fff;
        }
        button[type="submit"]:hover, button[style*="gradient"]:hover {
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }
        .peer:placeholder-shown + label {
            display: none;
        }

        .peer:not(:placeholder-shown) + label,
        .peer:focus + label {
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