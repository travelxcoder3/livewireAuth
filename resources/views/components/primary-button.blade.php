@props([
    'type' => 'button',
    'href' => null,
    'color' => 'primary',
    'textColor' => 'white',
    'width' => null,
    'padding' => 'px-4 py-2',
    'fontSize' => 'text-sm',
    'rounded' => 'rounded-xl',
    'shadow' => 'shadow transition duration-300 cursor-pointer hover:opacity-70',
    'gradient' => true,
    'icon' => null,

    // جديد: دعم حالة التحميل الموجّهة لـ Livewire
    'loading' => false,        // فعّل السبينر وتعطيل الزر أثناء الطلب
    'target'  => null,         // اسم الميثود: مثال downloadSingleInvoicePdf
    'busyText'=> null,         // نص أثناء التحميل
])

@php
    $baseStyle = '';
    if ($gradient && $color === 'primary') {
        $baseStyle = 'background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);';
    } elseif (!$gradient && $color !== 'primary') {
        $baseStyle = "background-color: {$color};";
    }

    $classes = trim("{$padding} {$fontSize} font-bold text-{$textColor} {$rounded} {$shadow} transition duration-300 flex items-center gap-2 justify-center " . ($width ?? ''));
@endphp

@if ($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => $classes, 'style' => $baseStyle]) }}>
        @if (!is_null($icon))
            {!! $icon !== '' ? $icon : $defaultIcon !!}
        @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes, 'style' => $baseStyle]) }}
        @if($loading) wire:loading.attr="disabled" @endif
        @if($loading && $target) wire:target="{{ $target }}" @endif
    >
        @if (!is_null($icon))
            {!! $icon !== '' ? $icon : $defaultIcon !!}
        @endif

        {{-- نص عادي أثناء السكون --}}
        <span @if($loading) wire:loading.remove @if($target) wire:target="{{ $target }}" @endif @endif>
            {{ $slot }}
        </span>

        {{-- سبينر + نص أثناء التحميل --}}
        @if($loading)
            <span class="inline-flex items-center gap-2"
                  wire:loading.delay.shortest @if($target) wire:target="{{ $target }}" @endif>
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                    <path d="M4 12a8 8 0 018-8" fill="currentColor" class="opacity-75"></path>
                </svg>
                <span>{{ $busyText ?? 'جاري المعالجة…' }}</span>
            </span>
        @endif
    </button>
@endif