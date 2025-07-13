@props([
    'name' => '',
    'label' => '',
    'placeholder' => '',
    'wireModel' => '',
    'type' => 'text',
    'isSelect' => false,
    'options' => [],
    'width' => 'w-full',
    'height' => '',
    'wireChange' => null,
    'wireInput' => null,
    'containerClass' => 'relative mt-1',
    'fieldClass' => 'peer rounded-lg border border-gray-300 px-3 py-2 bg-white text-sm placeholder-transparent text-gray-600 
                    focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500))] focus:border-[rgb(var(--primary-500))] transition duration-200',
    'labelClass' => 'absolute right-3 -top-2.5 px-1 bg-white text-xs text-gray-500 transition-all
                    peer-placeholder-shown:top-2 peer-placeholder-shown:text-sm peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[rgb(var(--primary-600))]',
])
<div class="{{ $containerClass }}">
    @php
        $finalFieldClass = "$width $height $fieldClass";
    @endphp

    @if($isSelect)
        <select wire:model="{{ $wireModel }}" 
                name="{{ $name }}" 
                id="{{ $name }}" 
                class="{{ $finalFieldClass }}"
                @if($wireChange) wire:change="{{ $wireChange }}" @endif>
            <option value="">{{ $placeholder }}</option>
            @foreach($options as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $type }}"
               wire:model="{{ $wireModel }}"
               @if($wireChange) wire:change="{{ $wireChange }}" @endif
               @if($wireInput) wire:input="{{ $wireInput }}" @endif
               name="{{ $name }}"
               id="{{ $name }}"
               class="{{ $finalFieldClass }}"
               placeholder="{{ $placeholder ?: ' ' }}" />
    @endif

    <label for="{{ $name }}" class="{{ $labelClass }}">{{ $label }}</label>

    @error($wireModel)
        <span class="text-red-600 text-xs">{{ $message }}</span>
    @enderror
</div>