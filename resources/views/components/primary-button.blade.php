@props([
    'type' => 'button',
    'color' => 'primary',
    'textColor' => 'white',
    'width' => null,
    'padding' => 'px-4 py-2',
    'fontSize' => 'text-sm',
    'rounded' => 'rounded-xl',
    'shadow' => 'shadow transition duration-300 cursor-pointer hover:opacity-70',
    'gradient' => true,
    'icon' => null, // SVG أو نص يُمرر كأيقونة
])

@php
    $baseStyle = '';
    if ($gradient && $color === 'primary') {
        $baseStyle = 'background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);';
    } elseif (!$gradient && $color !== 'primary') {
        $baseStyle = "background-color: {$color};";
    }

    // أيقونة افتراضية إذا ما تم تمرير أيقونة
    $defaultIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "{$padding} {$fontSize} font-bold text-{$textColor} {$rounded} {$shadow} transition duration-300 flex items-center gap-2 justify-center " . ($width ?? ''),
        'style' => $baseStyle,
    ]) }}
>
    @if (!is_null($icon))
        {!! $icon !== '' ? $icon : $defaultIcon !!}
    @endif
    {{ $slot }}
</button>
