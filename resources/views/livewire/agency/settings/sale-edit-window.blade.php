@php
    use Illuminate\Support\Facades\Auth;
@endphp

<div class="space-y-6">
    @if($successMessage)
        <div x-data="{ show: true }" x-init="setTimeout(()=>show=false,2000)" x-show="show"
             class="fixed bottom-4 right-4 text-white px-4 py-2 rounded-md shadow text-sm z-50"
             style="background-color: rgb(var(--primary-500));">
            {{ $successMessage }}
        </div>
    @endif

    <h1 class="text-2xl font-bold text-[rgb(var(--primary-700))]">إعداد مهلة تعديل المبيعات</h1>

    <div class="bg-white border rounded-xl p-4 space-y-4">
        <div class="text-sm text-gray-600">
            القيمة بوحدة ساعة. 0 يعني بدون تقييد زمني.
        </div>

        {{-- إن كان لديك x-input-field و x-primary-button استخدمهما، وإلا استبدل بحقول HTML عادية --}}
        <x-input-field
            name="hours"
            label="عدد الساعات المسموحة للتعديل"
            wireModel="hours"
            type="number" step="1"
            placeholder="مثال: 72"
            containerClass="relative mt-1 max-w-xs"
            errorName="hours"
        />

        <div class="pt-2">
            <x-primary-button type="button" wire:click="save" textColor="white">
                حفظ
            </x-primary-button>
        </div>
    </div>

    <div class="text-xs text-gray-500">
        الوكالة: {{ Auth::user()->agency->name ?? '—' }}
    </div>
</div>
