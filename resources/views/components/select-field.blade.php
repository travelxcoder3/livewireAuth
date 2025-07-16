@props([
    'label' => '',
    'options' => [],
    'selected' => '',
    'name' => '',
    'wireModel' => '',
    'placeholder' => 'اختر',
    'containerClass' => 'relative',
    'errorName' => '',
])

<div
    x-data="{
        open: false,
        selected: '{{ $selected }}',
        menuWidth: 0,
        init() {
            this.menuWidth = this.$refs.trigger.offsetWidth;
        }
    }"
    x-init="init()"
    class="{{ $containerClass }} w-full"
>
    {{-- الزر --}}
    <div
        @click="open = !open"
        x-ref="trigger"
        class="cursor-pointer border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm text-gray-700 flex justify-between items-center
               hover:ring-2 hover:ring-[rgb(var(--primary-500))] transition duration-150">
        <span x-text="selected || '{{ $placeholder }}'" class="truncate"></span>
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </div>

    {{-- القائمة --}}
    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute z-50 mt-1 bg-white border border-gray-300 rounded-md shadow-md max-h-60 overflow-auto"
        :style="'width: ' + menuWidth + 'px'"
    >
        @foreach($options as $key => $value)
            <div
                @click="selected = '{{ $value }}'; $wire.set('{{ $wireModel }}', '{{ $key }}'); open = false"
                class="px-3 py-2 hover:bg-[rgb(var(--primary-100))] text-sm text-gray-700 cursor-pointer transition"
                :class="{ 'bg-[rgb(var(--primary-500))] text-white': selected === '{{ $value }}' }"
            >
                {{ $value }}
            </div>
        @endforeach
    </div>


    <input type="hidden" name="{{ $name }}" :value="selected">
<span class="text-xs block min-h-[0.75rem] leading-tight mt-0.5">
    @error($errorName ?: $wireModel)
        <span class="text-red-600">{{ $message }}</span>
    @enderror
</span></div>
