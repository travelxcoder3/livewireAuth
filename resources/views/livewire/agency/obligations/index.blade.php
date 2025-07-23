<div>

@php
    use App\Services\ThemeService;
    $themeName = strtolower(Auth::user()?->agency?->theme_color ?? 'emerald');
    $colors = ThemeService::getCurrentThemeColors($themeName);
    $user = auth()->user();
@endphp

{{-- رسائل الجلسة للتصحيح --}}
@if (session('message'))
    <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 6px; margin-bottom: 10px;">{{ session('message') }}</div>
@endif
@if (session('error'))
    <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 10px;">{{ session('error') }}</div>
@endif
@if (session('debug'))
    <div style="background: #fef9c3; color: #92400e; padding: 10px; border-radius: 6px; margin-bottom: 10px;">{{ session('debug') }}</div>
@endif

<div class="space-y-6">
    <!-- العنوان والرسائل -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold"
            style="color: rgb(var(--primary-700)); border-bottom: 2px solid rgba(var(--primary-200), 0.5); padding-bottom: 0.5rem;">
            إدارة الالتزامات
        </h2>

        @if(session('message'))
            <div class="bg-white rounded-md px-4 py-2 text-center shadow text-sm"
                 style="color: rgb(var(--primary-700)); border: 1px solid rgba(var(--primary-200), 0.5);">
                {{ session('message') }}
            </div>
        @endif
    </div>

    <!-- محتوى الصفحة -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" style="color: rgb(var(--primary-700));">قائمة الالتزامات</h2>

            <button wire:click="create"
                    class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                    style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                + إضافة التزام جديد
            </button>
        </div>

        <!-- حقل البحث -->
        <div class="mb-4">
            <input type="text" wire:model.live="search" placeholder="ابحث في الالتزامات..." 
                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))]">
        </div>

        <!-- جدول الالتزامات -->
       @php use App\Tables\ObligationTable; @endphp

            <x-data-table 
                :rows="$obligations" 
                :columns="ObligationTable::columns()" 
            />


        <div class="mt-4">
            {{ $obligations->links() }}
        </div>
    </div>

    <!-- نافذة التحرير والإضافة -->
    @if($showModal)
        <div class="fixed inset-0 z-50 bg-black/10 flex items-start justify-center pt-24 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 relative transform transition-all duration-300">
                <button wire:click="$set('showModal', false)"
                        class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                    &times;
                </button>

                <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                    {{ $selectedObligation ? 'تعديل الالتزام' : 'إضافة التزام جديد' }}
                </h3>

                <form wire:submit.prevent="save" class="space-y-4 text-sm">
                    @php
                        $fieldClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] focus:outline-none bg-white text-xs peer';
                        $labelClass = 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]';
                        $containerClass = 'relative mt-1';
                    @endphp

                    <!-- عنوان الالتزام -->
                    <div class="{{ $containerClass }}">
                        <input type="text" wire:model="title" id="title" class="{{ $fieldClass }}" placeholder="عنوان الالتزام" />
                        <label for="title" class="{{ $labelClass }}">العنوان</label>
                        @error('title') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- وصف الالتزام -->
                    <div class="{{ $containerClass }}">
                        <textarea wire:model="description" id="description" rows="3" class="{{ $fieldClass }}" placeholder="وصف الالتزام"></textarea>
                        <label for="description" class="{{ $labelClass }}">الوصف</label>
                        @error('description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- تواريخ الالتزام -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model="start_date" id="start_date" class="{{ $fieldClass }}" />
                            <label for="start_date" class="{{ $labelClass }}">تاريخ البدء</label>
                            @error('start_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="{{ $containerClass }}">
                            <input type="date" wire:model="end_date" id="end_date" class="{{ $fieldClass }}" />
                            <label for="end_date" class="{{ $labelClass }}">تاريخ الانتهاء</label>
                            @error('end_date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- تطبيق على الجميع -->
                     <!-- داخل النافذة المنبثقة -->
<!-- ... حقول العنوان والوصف والتواريخ ... -->


<div class="mb-4">
  <!-- Container أبيض مع ظل وزوايا مستديرة -->
  <div class="bg-white rounded-lg shadow p-4 max-h-64 overflow-y-auto">
    <div class="flex justify-between items-center mb-3">
      <span class="font-medium text-gray-700">الموظفون المعنيون</span>
      <!-- زر تحديد/إلغاء الكل بنمط الثيم -->
      <button
        type="button"
        wire:click="toggleSelectAll"
        class="text-white font-bold text-sm px-3 py-1 rounded-xl shadow-md transition duration-300"
        style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);"
      >
        {{ count($selectedUsers) < $employees->count() ? 'تحديد الكل' : 'إلغاء التحديد' }}
      </button>
    </div>

    <ul class="space-y-2">
      @foreach($employees as $emp)
        <li class="flex items-center">
          <input
            wire:model="selectedUsers"
            type="checkbox"
            value="{{ $emp->id }}"
            @if($for_all) disabled @endif
            class="h-4 w-4 text-[rgb(var(--primary-600))] border-gray-300 rounded"
          />
          <label class="mr-2 text-gray-600">{{ $emp->name }}</label>
        </li>
      @endforeach
    </ul>
  </div>

  @error('selectedUsers')
    <span class="text-red-500 text-xs">{{ $message }}</span>
  @enderror
</div>


<!-- ... أزرار الإلغاء والحفظ ... -->

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                            إلغاء
                        </button>
                        <button type="submit"
                                class="text-white font-bold px-4 py-2 rounded-xl shadow-md transition duration-300 text-sm"
                                style="background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);">
                            {{ $selectedObligation ? 'تحديث' : 'إضافة' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
<!-- نافذة تأكيد الحذف -->
@if($showDeleteModal)
    <div class="fixed inset-0 z-50 bg-black/10 flex items-center justify-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 relative transform transition-all duration-300">
            <button wire:click="$set('showDeleteModal', false)"
                    class="absolute top-3 left-3 text-gray-400 hover:text-red-500 text-xl font-bold">
                &times;
            </button>

            <h3 class="text-xl font-bold mb-4 text-center" style="color: rgb(var(--primary-700));">
                تأكيد الحذف
            </h3>

            <p class="text-sm text-gray-600 mb-6 text-center">هل أنت متأكد من رغبتك في حذف هذا الالتزام؟ لا يمكن التراجع عن هذا الإجراء.</p>

            <div class="flex justify-center gap-3 pt-4">
                <button type="button" wire:click="$set('showDeleteModal', false)"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-xl shadow transition duration-300 text-sm">
                    إلغاء
                </button>
                
                <x-primary-button wire:click="delete">
                    حذف
                </x-primary-button>
            </div>
        </div>
    </div>
@endif
    <!-- تنبيه عائم -->
    @if(session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show" x-transition
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm"
             style="background-color: rgb(var(--primary-500));">
            {{ session('message') }}
        </div>
    @endif

    <style>
        .peer:placeholder-shown + label {
            top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .peer:not(:placeholder-shown) + label,
        .peer:focus + label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: rgb(var(--primary-600));
        }

        button[wire\:click="create"]:hover,
        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--primary-500), 0.2);
        }

        button[wire\:click="create"]:active,
        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
</div>
</div>