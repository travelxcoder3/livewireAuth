<div class="space-y-6">
    <div class="flex justify-between items-center">
        <!-- العنوان (أقصى اليمين) -->
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة العملاء
        </h2>
        <!-- العميل الجديد (أقصى اليسار) -->
        <div class="flex items-center gap-4">
            <!-- زر التنظيف (بجواره) -->
            <button wire:click="resetFilters" type="button"
                class="text-sm px-4 py-2 bg-white border border-[rgb(var(--primary-500))] text-[rgb(var(--primary-500))] hover:bg-[rgb(var(--primary-50))] hover:shadow-md rounded-lg transition">
                تنظيف الفلاتر
            </button>
            @can('customers.create')
            <x-primary-button wire:click="openModal" class="text-sm">
                + إضافة عميل جديد
            </x-primary-button>
            @endcan
        </div>
        <x-toast />
    </div>
    <!-- ✅ مودال إضافة/تعديل عميل -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 bg-black/30 backdrop-blur-sm flex justify-center items-start pt-24">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl mx-4 p-6 relative">
                <button wire:click="closeModal"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>

                <h2 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $editingId ? 'تعديل عميل' : 'إضافة عميل جديد' }}
                </h2>

                <form wire:submit.prevent="save" class="space-y-4 text-sm">
                    <div class="grid md:grid-cols-2 gap-3">
                        <!-- السطر الأول -->
                        <x-input-field wireModel="name" label="الاسم الكامل" placeholder="الاسم الكامل"
                            errorName="name" />

                        <x-input-field wireModel="email" label="البريد الإلكتروني" placeholder="البريد الإلكتروني"
                            errorName="email" type="email" />

                        <!-- السطر الثاني -->
                        <x-input-field wireModel="phone" label="رقم الهاتف" placeholder="رقم الهاتف"
                            errorName="phone" />

                        <x-input-field wireModel="address" label="العنوان" placeholder="العنوان" errorName="address" />
                    </div>


                    <x-checkbox-field name="has_commission" wireModel="has_commission" label="هل لهذا العميل عمولة؟"
                        containerClass="mt-3 col-span-4" />

                    <div class="flex justify-center gap-4 pt-6">
                        <x-primary-button type="submit">
                            {{ $editingId ? 'تأكيد التعديل' : 'حفظ العميل' }}
                        </x-primary-button>

                        <x-primary-button type="button" wire:click="closeModal" color="bg-gray-200"
                            textColor="text-gray-800" :gradient="false">
                            إلغاء
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    <!-- الفلترة -->
    <div class="bg-white rounded-xl shadow-md p-4 mb-4 grid md:grid-cols-4 gap-3 text-sm">
        <x-input-field wireModel="search" label="الاسم أو البريد الإلكتروني" placeholder="ابحث بالاسم أو البريد" />
        <x-input-field wireModel="phoneFilter" label="رقم الهاتف" placeholder="ابحث برقم الهاتف" />
        <x-input-field wireModel="addressFilter" label="العنوان" placeholder="ابحث بالعنوان" />
        <div class="relative mt-0.5">
            <select wire:model.live="commissionFilter" id="commissionFilter"
                class="w-full rounded-lg border border-gray-300 text-xs py-2 px-3 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white">
                <option value="">الكل</option>
                <option value="1">نعم</option>
                <option value="0">لا</option>
            </select>
            <label for="commissionFilter"
                class="absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-focus:-top-2.5 peer-focus:text-xs">
                العمولة
            </label>
        </div>
    </div>
    <!-- جدول العملاء -->
    @php
        use App\Tables\CustomerTable;
        $columns = CustomerTable::columns();
    @endphp
    <x-data-table :rows="$customers" :columns="$columns" />
</div>
