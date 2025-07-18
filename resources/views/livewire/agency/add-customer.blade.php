<div class="space-y-6">
    <!-- العنوان والتنبيه -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold" style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة العملاء
        </h2>

        @if(session('success'))
            <div class="bg-white rounded-md px-4 py-2 text-center shadow text-sm" style="color: rgb(var(--primary-700)); border: 1px solid rgba(var(--primary-200), 0.5);">
                {{ session('success') }}
            </div>
        @endif
    </div>

    <!-- نموذج الإضافة / التعديل -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
            {{ $editingId ? 'تعديل عميل' : 'إضافة عميل جديد' }}
        </h2>

        <form wire:submit.prevent="save" class="space-y-4 text-sm">
            <div class="grid md:grid-cols-4 gap-3">
                <!-- حقل الاسم -->
                <x-input-field 
                    wireModel="name"
                    label="الاسم الكامل"
                    placeholder="الاسم الكامل"
                    errorName="name"
                />

                <!-- حقل البريد الإلكتروني -->
                <x-input-field 
                    type="email"
                    wireModel="email"
                    label="البريد الإلكتروني"
                    placeholder="البريد الإلكتروني"
                    errorName="email"
                />

                <!-- حقل الهاتف -->
                <x-input-field 
                    wireModel="phone"
                    label="رقم الهاتف"
                    placeholder="رقم الهاتف"
                    errorName="phone"
                />

                <!-- حقل العنوان -->
                <x-input-field 
                    wireModel="address"
                    label="العنوان"
                    placeholder="العنوان"
                    errorName="address"
                />
            </div>

            <!-- حقل العمولة -->
            <div class="flex items-center mt-3 col-span-4">
                <input type="checkbox" wire:model.defer="has_commission" id="has_commission"
                    class="rounded border-gray-300 text-[rgb(var(--primary-600))] shadow-sm focus:ring-[rgb(var(--primary-500))]">
                <label for="has_commission" class="text-sm text-gray-700 mr-2">
                    هل لهذا العميل عمولة؟
                </label>
            </div>

            <!-- الأزرار باستخدام مكون primary-button -->
            <div class="flex flex-col sm:flex-row justify-center gap-3 pt-4">
                <x-primary-button type="submit" class="w-full sm:w-auto">
                    {{ $editingId ? 'تأكيد التعديل' : 'حفظ العميل' }}
                </x-primary-button>

                <x-primary-button 
                    type="button" 
                    wire:click="resetFields"
                    color="bg-gray-200" 
                    textColor="text-gray-800" 
                    :gradient="false"
                    class="w-full sm:w-auto hover:bg-gray-300"
                >
                    تنظيف الحقول
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- جدول العملاء -->
    @php
        use App\Tables\CustomerTable;
        $columns = CustomerTable::columns();
    @endphp
    <x-data-table :rows="$customers" :columns="$columns" />

    <!-- Toast Success -->
    @if(session()->has('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 2000)"
             x-show="show"
             x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
             style="background-color: rgb(var(--primary-500));">
            {{ session('success') }}
        </div>
    @endif
</div>