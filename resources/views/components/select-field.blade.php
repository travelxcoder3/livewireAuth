@props([
    'label' => '',
    'options' => [],              // تُستخدم لأول تحميل فقط
    'selected' => '',
    'name' => '',
    'wireModel' => '',
    'placeholder' => 'اختر',
    'containerClass' => 'relative mt-1',
    'errorName' => '',
    'disabled' => false,
    'searchKey' => '',
    'optionsWire' => null,        // اسم خاصية Livewire (مثلاً: customerOptions / providerOptions)
    'selectedLabelWire' => null,
    'compact' => false,
])

@php
    $enableSearch = $searchKey || $optionsWire || count($options) > 5;
@endphp

<div
{{ $attributes->merge(['class' => $containerClass.' w-full']) }}
    x-data="{
        open: false,
        selected: @entangle($wireModel).live,
        selectedLabel: '',    // ← نخزن نص الخيار المختار
        menuWidth: 0,
        searchQuery: '',
        activeIndex: -1,      // ← إضافة مؤشر العنصر النشط

        // خيارات أول تحميل + placeholder
        options: Object.assign({ '': '{{ $placeholder }}' }, @js($options) || {}),

init() {
    this.menuWidth = this.$refs.trigger?.offsetWidth || 0;

    if (this.selected && this.options[this.selected] === undefined) {
        try {
            const lbl = {{ $selectedLabelWire ? "\$wire.{$selectedLabelWire}" : 'null' }};
            if (lbl) {
                this.options = Object.assign({ [this.selected]: lbl }, this.options);
                this.selectedLabel = lbl;
            }
        } catch(e) {}
    } else if (this.selected && this.options[this.selected] !== undefined) {
        this.selectedLabel = this.options[this.selected];
    }

    if (!this.selected) {
        this.selectedLabel = '';
    }

    // 👈 إضافة مهمّة: راقب selected دائماً
    this.$watch('selected', (val) => {
        if (!val) {
            this.selectedLabel = '';
        } else if (this.options && this.options[val] !== undefined) {
            this.selectedLabel = this.options[val];
        }
    });
},

handleArrowKeys(event) {
    if (!this.open) return;
    
    const entries = this.filteredEntries;
    if (entries.length === 0) return;
    
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        this.activeIndex = (this.activeIndex + 1) % entries.length;
        this.$nextTick(() => {
            const activeElement = this.$refs[`option-${this.activeIndex}`];
            if (activeElement) {
                activeElement.scrollIntoView({ block: 'nearest' });
            }
        });
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        this.activeIndex = (this.activeIndex - 1 + entries.length) % entries.length;
        this.$nextTick(() => {
            const activeElement = this.$refs[`option-${this.activeIndex}`];
            if (activeElement) {
                activeElement.scrollIntoView({ block: 'nearest' });
            }
        });
    } else if (event.key === 'Enter' && this.activeIndex >= 0) {
        event.preventDefault();
        this.selectActiveItem();
    } else if (event.key === ' ' && this.activeIndex >= 0) { // ← إضافة معالجة Space
        event.preventDefault();
        this.selectActiveItem();
    }
},

// 👈 إضافة دالة جديدة لاختيار العنصر النشط
selectActiveItem() {
    const entries = this.filteredEntries;
    if (this.activeIndex >= 0 && this.activeIndex < entries.length) {
        const [key, value] = entries[this.activeIndex];
        this.selected = key;
        this.selectedLabel = value;
        this.$wire.set('{{ $wireModel }}', key);
        this.open = false;
        this.activeIndex = -1;
    }
},



        // بحث ضبابي بسيط
        fuzzySearch(query, items) {
            if (!query) return items;
            const q = (query || '').toLowerCase();
            return items.filter(([key, value]) => {
                const v = String(value || '').toLowerCase();
                let qi = 0, vi = 0;
                while (vi < v.length && qi < q.length) {
                    if (v[vi] === q[qi]) qi++;
                    vi++;
                }
                return qi === q.length;
            });
        },

        get filteredEntries() {
            const entries = Object.entries(this.options || {});
            const result  = this.fuzzySearch(this.searchQuery, entries);
            const first   = ['', (this.options && this.options['']) ?? '{{ $placeholder }}'];
            const rest    = result.filter(([k]) => k !== '');
            return [first, ...rest];
        }
    }"

    {{-- حدّث قائمة الخيارات من خاصية Livewire لكن لا تمسح selected --}}
    x-on:lw-dropdowns-cleared.window="selected=''; selectedLabel=''; searchQuery='';"


x-effect="
    @if($optionsWire)
        (function(){
            const liveOpts = $wire.{{ $optionsWire }} ?? {};
            options = Object.assign({ '': '{{ $placeholder }}' }, liveOpts);

            // ✅ لو مافي selected (null/''), نظّف اللِّـيبل
            if (!selected) {
                selectedLabel = '';
                return;
            }

            if (options[selected] !== undefined) {
                selectedLabel = options[selected];
            } 
            else if ({{ $selectedLabelWire ? "\$wire.{$selectedLabelWire} != null" : 'false' }}) {
                const lbl = $wire.{{ $selectedLabelWire }};
                if (lbl) {
                    options[selected] = lbl;
                    selectedLabel = lbl;
                } else {
                    selectedLabel = '';
                }
            } else {
                selectedLabel = '';
            }
        })();
    @endif
    menuWidth = $refs.trigger.offsetWidth || 0;
"


    class="{{ $containerClass }} w-full"
>
    <!-- الحقل -->
<div
    @click="if (!{{ $disabled ? 'true' : 'false' }}) { 
                open = !open; 
                activeIndex = -1;
                $nextTick(() => { menuWidth = $refs.trigger?.offsetWidth || 0 })
            }"
    @keydown.tab="open = false; activeIndex = -1;"
    @keydown.enter.prevent.stop="if (!{{ $disabled ? 'true' : 'false' }}) { if (open && activeIndex >= 0) { selectActiveItem(); } else { open = !open; activeIndex = -1; } }"
    @keydown.space.prevent="if (!{{ $disabled ? 'true' : 'false' }}) { 
    if (open && activeIndex >= 0) {
        selectActiveItem(); 
    } else {
        open = !open; 
        activeIndex = -1;
    }
}"    @keydown.enter.prevent.stop="selectActiveItem()" @keydown="handleArrowKeys($event)"
    tabindex="0"
    x-ref="trigger"
    class="w-full rounded-lg border border-gray-300 px-3 {{ $compact ? 'py-1.5 text-sm' : 'py-2 text-xs' }} focus:ring-2  focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white cursor-pointer flex items-center justify-between peer"
    :class="{ 'bg-gray-100 cursor-not-allowed': {{ $disabled ? 'true' : 'false' }} }"
>
<span class="flex-1 min-w-0 truncate"
      x-text="selectedLabel || (options && options[selected]) || '{{ $placeholder }}'"></span>

        </span>
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"
             :class="{ 'hidden': {{ $disabled ? 'true' : 'false' }} }">
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </div>

    <!-- التسمية -->
    <label class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]">
        {{ $label }}
    </label>

    <!-- القائمة -->
        <div
            x-show="open"
            x-transition
        @click.outside="open = false; searchQuery = ''; activeIndex = -1;"
            class="absolute z-50 mt-1 bg-white border border-gray-300 rounded-md shadow-md max-h-60 overflow-auto"
            :style="'width: ' + menuWidth + 'px'"
            @keydown.escape="open = false; activeIndex = -1;"
        >
        @if($enableSearch)
            <div class="sticky top-0 bg-white p-2 border-b">
                <input
                    x-model.debounce.300ms="searchQuery"
                    @input.debounce.300ms="{{ $searchKey ? "\$wire.set('{$searchKey}', searchQuery)" : '' }}"
                    type="text"
                    placeholder="ابحث..."
                    class="w-full px-2 py-1 text-xs border rounded focus:ring-1 focus:ring-[rgb(var(--primary-500))]"
                    @click.stop
                    @keydown.escape="open = false; activeIndex = -1;"
                    @keydown.enter.prevent.stop="selectActiveItem()" @keydown="handleArrowKeys($event)"
                />
            </div>
        @endif

<template x-for="[key, value, index] in filteredEntries.map((entry, i) => [...entry, i])" :key="key">
    <div
        @click="
            selected = key;
            selectedLabel = value;
            $wire.set('{{ $wireModel }}', key);
            open = false;
            activeIndex = -1;
        "
        :class="{
            'bg-[rgb(var(--primary-100))]': index === activeIndex,
            'bg-[rgb(var(--primary-500))] text-white': selected === key
        }"
        class="px-3 py-2 text-sm text-gray-700 cursor-pointer transition"
        x-ref="option-${index}"
        x-bind:data-index="index"
    >
        <span x-text="value"></span>
    </div>
</template>

        <div x-show="filteredEntries.length === 0" class="px-3 py-2 text-sm text-gray-500">
            لا توجد نتائج مطابقة
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" :value="selected">

        @unless($compact)
        <span class="text-xs block min-h-[0.75rem] leading-tight mt-0.5">
            @error($errorName ?: $wireModel)
                <span class="text-red-600">{{ $message }}</span>
            @enderror
        </span>
        @endunless

</div>
