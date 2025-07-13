@props(['title', 'align' => 'center', 'level' => 'h1', 'icon' => null])

@php
    $tag = in_array($level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? $level : 'h1';
    $titleClasses = match ($tag) {
        'h1' => 'text-3xl',
        'h2' => 'text-2xl',
        'h3' => 'text-xl',
        'h4' => 'text-lg',
        'h5' => 'text-base',
        'h6' => 'text-sm',
        default => 'text-3xl',
    };

    $justify = match ($align) {
        'right' => 'justify-end',
        'left' => 'justify-start',
        default => 'justify-center',
    };

    $textAlign = match ($align) {
        'right' => 'text-right',
        'left' => 'text-left',
        default => 'text-center',
    };
@endphp

<div class="flex items-center justify-between mb-8 flex-wrap gap-2">
    {{-- العنوان --}}
    <div class="flex-1 {{ $textAlign }} {{ $justify }} space-y-2">
        @if ($icon)
            <div class="{{ $align === 'right' ? 'ml-auto' : ($align === 'left' ? 'mr-auto' : 'mx-auto') }} h-16 w-16 rounded-full flex items-center justify-center mb-2"
                style="background: linear-gradient(to right, rgb(var(--primary-500)), rgb(var(--primary-600)));">
                {!! $icon !!}
            </div>
        @endif

        <{{ $tag }} class="{{ $titleClasses }} font-extrabold tracking-wide"
            style="color: rgb(var(--primary-700));">
            {{ $title }}
            </{{ $tag }}>
    </div>

    {{-- زر مخصص أو عناصر إضافية يتم تمريرها --}}
    @if (trim($slot))
        <div class="flex-shrink-0">
            {{ $slot }}
        </div>
    @endif
</div>
