@props([
    'name' => '',
    'label' => '',
    'wireModel' => '',
    'checked' => false,
    'containerClass' => '',
    'fieldClass' => '',
    'labelClass' => '',
    'errorName' => null
])

<div class="{{ $containerClass }}">
    <label class="flex items-center space-x-2 space-x-reverse cursor-pointer">
        <input 
            type="checkbox"
            name="{{ $name }}"
            wire:model="{{ $wireModel }}"
            {{ $checked ? 'checked' : '' }}
            class="h-4 w-4 rounded border-gray-300 focus:ring-[rgb(var(--primary-500))] text-[rgb(var(--primary-500))] accent-[rgb(var(--primary-500))] {{ $fieldClass }}"
        />
        <span class="{{ $labelClass }}">{{ $label }}</span>
    </label>
    
    @if($errorName)
        @error($errorName)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>