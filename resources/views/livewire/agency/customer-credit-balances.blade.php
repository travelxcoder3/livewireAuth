<div>
<div class="space-y-6">

    <!-- العنوان + زر الرجوع في صف واحد -->
    <div class="flex justify-between items-center border-b pb-2 mb-4">
        <h2 class="text-xl font-bold text-[rgb(var(--primary-700))]">
            أرصدة العملاء
        </h2>

        <a href="{{ route('agency.collections') }}"
            class="flex items-center gap-2 px-4 py-2 rounded-lg border transition duration-200 text-sm font-medium
                bg-white border-[rgb(var(--primary-500))] text-[rgb(var(--primary-600))] hover:shadow-md hover:text-[rgb(var(--primary-700))]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-180" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span>رجوع</span>
        </a>
    </div>

    <!-- رسائل النظام -->
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

        <div class="flex items-center gap-4">
            <input type="text" 
                   wire:model.live.debounce.500ms="search" 
                   placeholder="بحث بالاسم، الهاتف أو رقم العميل..."
                  class="flex-1 px-4 py-2 border  border-gray-300 rounded-lg focus:outline-none focus:ring" />
            
            @if($search)
                <button wire:click="$set('search', '')" 
                        class="text-gray-500 hover:text-gray-700">
                    إلغاء البحث
                </button>
            @endif
        </div>

        @if($customers->isEmpty())
            <div class="text-center py-8 text-gray-500">
                @if($search)
                    لا توجد نتائج مطابقة للبحث "{{ $search }}"
                @else
                    لا توجد حسابات دائنة للعملاء
                @endif
            </div>
        @else
            <x-data-table :rows="$customers" :columns="$columns" />

           
        @endif
    </div>
</div>