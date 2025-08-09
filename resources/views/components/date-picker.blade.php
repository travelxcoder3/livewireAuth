@props([
    'name' => '',
    'label' => '',
    'placeholder' => '',
    'wireModel' => '',
    'width' => 'w-full',
    'height' => '',
    'errorName' => '',
    'containerClass' => 'relative mt-1',
    'fieldClass' => 'peer rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm placeholder-transparent text-gray-600 
                    focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] transition duration-200',
    'labelClass' => 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
                    peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))] cursor-text',
])
<div 
    x-data="{
        open: false,
        date: @entangle($wireModel).live,
        init() {
            // تهيئة القيمة إذا كانت فارغة
            if (!this.date) {
            }
        },
        formatDate(date) {
            if (!date) return '';
            const [year, month, day] = date.split('-');
            return `${year}-${month}-${day}`;
        }
    }"
    class="{{ $containerClass }}"
    x-on:keydown.escape="open = false"
>
    <!-- حقل الإدخال المخفي -->
    <input 
        type="hidden" 
        x-model="date"
        name="{{ $name }}"
    >
    
    <!-- حقل العرض -->
    <div 
        @click.stop="open = !open"
        class="{{ $width }} {{ $height }} {{ $fieldClass }} cursor-pointer flex justify-between items-center"
    >
        <span x-text="date ? formatDate(date) : '{{ $placeholder }}'" class="truncate"></span>
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
    </div>
    
    <!-- التسمية العائمة -->
    <label class="{{ $labelClass }}">
        {{ $label }}
    </label>
    
    <!-- التقويم -->
    <div 
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute z-50 mt-1 bg-white border border-gray-300 rounded-md shadow-md p-2 w-64"
        @click.stop
    >
        <div class="flex justify-between items-center mb-2">
            <button 
                @click.stop="
                    const d = new Date(date || new Date());
                    d.setMonth(d.getMonth() - 1);
                    date = d.toISOString().split('T')[0];
                "
                class="p-1 rounded hover:bg-gray-100"
                type="button"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            
            <span x-text="new Date(date || new Date()).toLocaleString('default', { month: 'long', year: 'numeric' })" class="font-medium"></span>
            
            <button 
                @click.stop="
                    const d = new Date(date || new Date());
                    d.setMonth(d.getMonth() + 1);
                    date = d.toISOString().split('T')[0];
                "
                class="p-1 rounded hover:bg-gray-100"
                type="button"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
        
        <div class="grid grid-cols-7 gap-1 mb-2">
            <template x-for="day in ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت']">
                <div class="text-center text-xs font-medium text-gray-500" x-text="day"></div>
            </template>
        </div>
        
        <div class="grid grid-cols-7 gap-1">
            <template x-for="i in new Date(new Date(date || new Date()).getFullYear(), new Date(date || new Date()).getMonth() + 1, 0).getDate()">
                <button
                    @click.stop="
                        const d = new Date(date || new Date());
                        d.setDate(i);
                        date = d.toISOString().split('T')[0];
                        open = false;
                    "
                    class="w-8 h-8 rounded-full text-sm flex items-center justify-center"
                    :class="{
                        'bg-[rgb(var(--primary-500))] text-white': date && new Date(date).getDate() === i && new Date(date).getMonth() === new Date(date || new Date()).getMonth(),
                        'hover:bg-gray-100': !(date && new Date(date).getDate() === i && new Date(date).getMonth() === new Date(date || new Date()).getMonth())
                    }"
                    x-text="i"
                    type="button"
                ></button>
            </template>
        </div>
    </div>
    
    <!-- رسالة الخطأ -->
    <span class="text-xs block min-h-[0.75rem] leading-tight mt-0.5">
        @error($errorName ?: $wireModel)
            <span class="text-red-600">{{ $message }}</span>
        @enderror
    </span>
</div>