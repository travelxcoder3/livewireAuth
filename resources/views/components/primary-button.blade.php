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
])

@php
    $baseStyle = '';
    if ($gradient && $color === 'primary') {
        $baseStyle = 'background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);';
    } elseif (!$gradient && $color !== 'primary') {
        $baseStyle = "background-color: {$color};";
    }

    $classes = "{$padding} {$fontSize} font-bold text-{$textColor} {$rounded} {$shadow} transition duration-300 flex items-center gap-2 justify-center " . ($width ?? '');
@endphp

@if ($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => $classes, 'style' => $baseStyle]) }}>
        @if (!is_null($icon))
            {!! $icon !== '' ? $icon : $defaultIcon !!}
        @endif
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes, 'style' => $baseStyle]) }}>
        @if (!is_null($icon))
            {!! $icon !== '' ? $icon : $defaultIcon !!}
        @endif
        {{ $slot }}
    </button>
@endif
