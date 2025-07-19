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
                    <div class="flex-1">
                        <x-input-field 
                            wireModel="newListName"
                            label="اسم القائمة"
                            placeholder="اسم القائمة"
                            errorName="newListName"
                            fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm placeholder-transparent text-gray-600 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] transition duration-200"
                            labelClass="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]"
                        />
                    </div>
                    
                    <x-primary-button type="submit" class="whitespace-nowrap">
                        حفظ
                    </x-primary-button>
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
                                @if ($editingItemId === $item->id)
                                    <form wire:submit.prevent="updateItem" class="flex items-center gap-2 w-full">
                                        <div class="flex-1">
                                            <x-input-field 
                                                wireModel="editingItemLabel"
                                                placeholder="تعديل البند"
                                                fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white"
                                                labelClass="hidden"
                                            />
                                        </div>
                                        
                                        <x-primary-button type="submit" padding="px-3 py-1.5" fontSize="text-xs">
                                            حفظ
                                        </x-primary-button>
                                        
                                        <x-primary-button 
                                            type="button" 
                                            wire:click="$set('editingItemId', null)"
                                            color="white"
                                            textColor="gray-600"
                                            padding="px-3 py-1.5"
                                            fontSize="text-xs"
                                            gradient="false"
                                            :border="true"
                                        >
                                            إلغاء
                                        </x-primary-button>
                                    </form>
                                @else
                                    <span class="text-gray-800 font-medium">{{ $item->label }}</span>
                                    <div class="flex items-center gap-2">
                                        <x-primary-button 
                                            wire:click="startEditItem({{ $item->id }})"
                                            color="white"
                                            textColor="primary-700"
                                            padding="px-3 py-1"
                                            fontSize="text-xs"
                                            rounded="rounded-xl"
                                            gradient="false"
                                            :border="true"
                                        >
                                            تعديل
                                        </x-primary-button>
                                        
                                        <x-primary-button 
                                            wire:click="deleteItem({{ $item->id }})"
                                            onclick="return confirm('هل أنت متأكد من حذف البند الرئيسي؟')"
                                            color="white"
                                            textColor="red-600"
                                            padding="px-3 py-1"
                                            fontSize="text-xs"
                                            rounded="rounded-xl"
                                            gradient="false"
                                            :border="true"
                                        >
                                            حذف
                                        </x-primary-button>
                                    </div>
                                @endif
                            </div>
                            
                            <ul class="space-y-3">
                                @foreach ($item->subItems as $sub)
                                    <li class="flex justify-between items-center px-2">
                                        @if ($editingSubItemId === $sub->id)
                                            <form wire:submit.prevent="updateSubItem" class="flex items-center gap-2 w-full">
                                                <div class="flex-1">
                                                    <x-input-field 
                                                        wireModel="editingSubItemLabel"
                                                        placeholder="تعديل البند الفرعي"
                                                        fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white"
                                                        labelClass="hidden"
                                                    />
                                                </div>
                                                
                                                <x-primary-button type="submit" padding="px-3 py-1.5" fontSize="text-xs">
                                                    حفظ
                                                </x-primary-button>
                                                
                                                <x-primary-button 
                                                    type="button" 
                                                    wire:click="$set('editingSubItemId', null)"
                                                    color="white"
                                                    textColor="gray-600"
                                                    padding="px-3 py-1.5"
                                                    fontSize="text-xs"
                                                    gradient="false"
                                                    :border="true"
                                                >
                                                    إلغاء
                                                </x-primary-button>
                                            </form>
                                        @else
                                            <span class="text-sm text-gray-600">{{ $sub->label }}</span>
                                            <div class="flex items-center gap-2">
                                                <x-primary-button 
                                                    wire:click="startEditSubItem({{ $sub->id }})"
                                                    padding="px-3 py-1.5"
                                                    fontSize="text-xs"
                                                >
                                                    تعديل
                                                </x-primary-button>
                                                
                                                <x-primary-button 
                                                    wire:click="deleteSubItem({{ $sub->id }})"
                                                    onclick="return confirm('هل أنت متأكد من الحذف؟')"
                                                    color="white"
                                                    textColor="red-600"
                                                    padding="px-3 py-1.5"
                                                    fontSize="text-xs"
                                                    gradient="false"
                                                    :border="true"
                                                >
                                                    حذف
                                                </x-primary-button>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            
                            <div class="flex items-center gap-2 mt-4">
                                <div class="flex-1">
                                    <x-input-field 
                                        wireModel="subItemLabel.{{ $item->id }}"
                                        placeholder="اسم البند الفرعي"
                                        fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white peer"
                                        labelClass="hidden"
                                    />
                                </div>
                                
                                <x-primary-button 
                                    wire:click="addSubItem({{ $item->id }})" 
                                    wire:loading.attr="disabled"
                                    padding="px-4 py-2"
                                    fontSize="text-xs"
                                    class="whitespace-nowrap"
                                >
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
                                </x-primary-button>
                            </div>
                            
                            @error("subItemLabel.$item->id")
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                    
                    <div class="flex items-center gap-2 mt-4">
                        <div class="flex-1">
                            <x-input-field 
                                wireModel="itemLabel.{{ $list->id }}"
                                placeholder="اسم البند الرئيسي"
                                fieldClass="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none bg-white"
                                labelClass="hidden"
                            />
                        </div>
                        
                        <x-primary-button 
                            wire:click="addItem({{ $list->id }})"
                            padding="px-4 py-2"
                            fontSize="text-xs"
                            class="whitespace-nowrap"
                        >
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
                        </x-primary-button>
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