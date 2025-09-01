<div class="space-y-6">
  <div class="bg-white rounded-xl shadow-md p-5 mb-6 space-y-4">
    <!-- صف العنوان وزر الإضافة -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">
            إدارة العملاء
        </h2>
        <!-- العميل الجديد (أقصى اليسار) -->
        <div class="flex items-center gap-4">
            <!-- زر التنظيف (بجواره) -->
          
            @can('customers.create')
            <x-primary-button wire:click="openModal" class="text-sm">
                إضافة عميل جديد
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

               <form wire:submit.prevent="save" class="space-y-4 text-sm" enctype="multipart/form-data">

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

                    <x-select-field
                        label="نوع الحساب"
                        name="account_type"
                        wireModel="account_type"
                        :options="[
                            'individual' => 'فرد',
                            'company' => 'شركة',
                            'organization' => 'منظمة'
                        ]"
                        placeholder="اختر نوع الحساب"
                    />

                    <div class="space-y-2">
                        <label class="block text-sm text-gray-700">صور العميل</label>

                        {{-- الصور الموجودة مسبقًا --}}
                      @foreach ($existingImages as $id => $path)
                            <div class="flex items-center gap-2 opacity-{{ in_array($id, $deletedImageIds) ? '50' : '100' }}">
                                <img src="{{ asset('storage/' . $path) }}" class="w-10 h-10 rounded border object-cover" alt="صورة حالية">
                                
                                @if (!in_array($id, $deletedImageIds))
                                    <button type="button" wire:click="deleteExistingImage({{ $id }})"
                                        class="text-red-600 text-xs hover:underline">حذف</button>
                                @else
                                    <span class="text-gray-500 text-xs">بانتظار التأكيد</span>
                                @endif
                            </div>
                        @endforeach


                        {{-- الصور الجديدة --}}
                        @foreach ($images as $index => $img)
                            <div class="flex items-center gap-2">
                                <input type="file" wire:model="images.{{ $index }}" class="text-sm border rounded p-1 w-full" />
                                <button type="button" wire:click="removeImage({{ $index }})"
                                    class="text-red-600 text-xs hover:underline">حذف</button>
                            </div>
                        @endforeach

                        <button type="button" wire:click="addImage"
                            class="text-[rgb(var(--primary-600))] hover:text-[rgb(var(--primary-700))] text-sm font-bold">
                            + أضف صورة جديدة
                        </button>

                        @error('images.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>




                       <x-checkbox-field name="has_commission" wireModel="has_commission" label="هل لهذا العميل عمولة؟"
                            containerClass="mt-3 col-span-4" />

                        <div class="flex justify-center gap-4 pt-6">
                       <x-primary-button
  type="button"
  x-data
  @click="window.dispatchEvent(new CustomEvent('confirm:open',{ detail:{
      title: '{{ $editingId ? 'تأكيد تعديل العميل' : 'تأكيد حفظ العميل' }}',
      message: '{{ $editingId ? 'سيتم تحديث بيانات هذا العميل. متابعة؟' : 'سيتم حفظ بيانات العميل الجديد. متابعة؟' }}',
      icon: '{{ $editingId ? 'info' : 'check' }}',
      confirmText: '{{ $editingId ? 'تعديل' : 'حفظ' }}',
      cancelText: 'إلغاء',
      onConfirm: 'save',   // ← هنا
      payload: null
  }}))"
>
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
<div class="bg-white grid md:grid-cols-5 gap-3 text-sm">

    <x-input-field wireModel="search" label="الاسم أو البريد الإلكتروني" placeholder="ابحث بالاسم أو البريد" />
    <x-input-field wireModel="phoneFilter" label="رقم الهاتف" placeholder="ابحث برقم الهاتف" />
    <x-input-field wireModel="addressFilter" label="العنوان" placeholder="ابحث بالعنوان" />

    <!-- فلتر العمولة -->
    <x-select-field
        label="العمولة"
        wireModel="commissionFilter"
        name="commissionFilter"
        :options="[
            '' => 'الكل',
            '1' => 'نعم',
            '0' => 'لا',
        ]"
    />


    <!-- فلتر نوع الحساب -->
   <x-select-field
        label="نوع الحساب"
        wireModel="accountTypeFilter"
        name="accountTypeFilter"
        :options="[
            '' => 'الكل',
            'individual' => 'فرد',
            'company' => 'شركة',
            'organization' => 'منظمة',
        ]"
    />

</div>

<div class="flex justify-end mt-2 mb-6 px-5">
    <button type="button" wire:click="resetFields"
        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm flex items-center gap-2 w-full sm:w-auto">
        تنظيف الحقول
    </button>
</div>
</div>



    <!-- جدول العملاء -->
    @php
        use App\Tables\CustomerTable;
        $columns = CustomerTable::columns();
    @endphp
    <x-data-table :rows="$customers" :columns="$columns" />
@if($showWallet && $walletCustomerId)
<livewire:agency.customer-wallet
    :customerId="$walletCustomerId"
    wire:key="wallet-{{ $walletCustomerId }}-{{ $walletNonce }}" />

@endif

<x-confirm-dialog />

</div>
