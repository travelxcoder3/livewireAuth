@props([
    'label' => '',
    'options' => [],
    'selected' => '',
    'name' => '',
    'wireModel' => '',
    'placeholder' => 'اختر',
    'containerClass' => 'relative mt-1',
    'errorName' => '',
])

@php
    $enableSearch = count($options) > 5; // تفعيل البحث إذا كان هناك أكثر من 5 خيارات
@endphp

<div
    x-data="{
        open: false,
        selected: '{{ $selected }}',
        menuWidth: 0,
        searchQuery: '',
        init() {
            this.menuWidth = this.$refs.trigger.offsetWidth;
        },
        get filteredOptions() {
            if (!this.searchQuery) return {{ json_encode($options) }};
            return Object.entries({{ json_encode($options) }}).filter(
                ([key, value]) => value.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        isOptionVisible(key, value) {
            if (!this.searchQuery) return true;
            return value.toLowerCase().includes(this.searchQuery.toLowerCase());
        }
    }"
    x-init="init()"
    class="{{ $containerClass }} w-full"
>
    <!-- حقل الاختيار مع التسمية العائمة -->
    <div 
        @click="open = !open"
        x-ref="trigger"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs cursor-pointer flex justify-between items-center peer"
    >
        <span x-text="selected || '{{ $placeholder }}'" class="truncate"></span>
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </div>
    
    <!-- التسمية العائمة -->
    <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
        {{ $label }}
    </label>

    <!-- القائمة المنسدلة -->
    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute z-50 mt-1 bg-white border border-gray-300 rounded-md shadow-md max-h-60 overflow-auto"
        :style="'width: ' + menuWidth + 'px'"
    >
        <!-- حقل البحث (يظهر فقط إذا كان هناك أكثر من 5 خيارات) -->
        @if($enableSearch)
        <div class="sticky top-0 bg-white p-2 border-b">
            <input 
                x-model="searchQuery"
                type="text" 
                placeholder="ابحث..."
                class="w-full px-2 py-1 text-xs border rounded focus:ring-1 focus:ring-[rgb(var(--primary-500))]"
                @click.stop
                @keydown.escape="open = false"
            >
        </div>
        @endif

        <!-- عرض جميع الخيارات مع إخفاء/إظهار حسب البحث -->
        @foreach($options as $key => $value)
            <div
                x-show="isOptionVisible('{{ $key }}', '{{ $value }}')"
                @click="selected = '{{ $value }}'; $wire.set('{{ $wireModel }}', '{{ $key }}'); open = false"
                class="px-3 py-2 hover:bg-[rgb(var(--primary-100))] text-sm text-gray-700 cursor-pointer transition"
                :class="{ 'bg-[rgb(var(--primary-500))] text-white': selected === '{{ $value }}' }"
            >
                {{ $value }}
            </div>
        @endforeach
    </div>

    <!-- حقل مخفي للقيمة المحددة -->
    <input type="hidden" name="{{ $name }}" :value="selected">
    
    <!-- رسالة الخطأ -->
    <span class="text-xs block min-h-[0.75rem] leading-tight mt-0.5">
        @error($errorName ?: $wireModel)
            <span class="text-red-600">{{ $message }}</span>
        @enderror
    </span>
</div>