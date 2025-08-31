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

    // التحميل الموجّه
    'loading' => false,      // تفعيل حالة التحميل
    'target'  => null,       // اسم/أسماء الميثود بدون بارامترات
    'busyText'=> null,       // نص أثناء التحميل
    'delay'   => 'auto',     // auto | shortest | default | longest
])

@php
    $baseStyle = '';
    if ($gradient && $color === 'primary') {
        $baseStyle = 'background: linear-gradient(to right, rgb(var(--primary-500)) 0%, rgb(var(--primary-600)) 100%);';
    } elseif (!$gradient && $color !== 'primary') {
        $baseStyle = "background-color: {$color};";
    }

    $classes = trim("{$padding} {$fontSize} font-bold text-{$textColor} {$rounded} {$shadow} transition duration-300 flex items-center gap-2 justify-center disabled:opacity-60 disabled:cursor-not-allowed " . ($width ?? ''));

    // دعم target كمصفوفة أو نص
    $targetList = is_array($target) ? implode(',', $target) : ($target ?: '');

    // تحديد التأخير تلقائياً لو كان الإجراء ثقيل (PDF/Export…)
    $isHeavy = $targetList && preg_match('/download|export|report|pdf|excel|generate|invoice/i', $targetList);
    $delayKey = $delay === 'auto' ? ($isHeavy ? 'longest' : 'shortest') : $delay;

    $delayAttr = match ($delayKey) {
        'longest'  => 'wire:loading.delay.longest',
        'default'  => 'wire:loading.delay',
        'shortest' => 'wire:loading.delay.shortest',
        default    => 'wire:loading.delay.shortest',
    };

    $showLoading = $loading && $targetList !== '';
    $busy = $busyText ?: 'جاري التنفيذ…';
@endphp

@if ($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => $classes, 'style' => $baseStyle]) }}>
        @if (!is_null($icon)) {!! $icon !== '' ? $icon : $defaultIcon !!} @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes, 'style' => $baseStyle]) }}
        @if($showLoading) wire:loading.attr="disabled" wire:target="{{ $targetList }}" @endif
        @if($showLoading) wire:loading.class="opacity-60 cursor-not-allowed" wire:target="{{ $targetList }}" @endif
        aria-busy="{{ $showLoading ? 'true' : 'false' }}"
    >
        @if (!is_null($icon)) {!! $icon !== '' ? $icon : $defaultIcon !!} @endif

        {{-- النص العادي --}}
        <span @if($showLoading) wire:loading.remove wire:target="{{ $targetList }}" @endif>
            {{ $slot }}
        </span>

        {{-- السبينر أثناء التحميل --}}
        @if($showLoading)
            <span class="inline-flex items-center gap-2"
                  {!! $delayAttr !!} @if($targetList) wire:target="{{ $targetList }}" @endif>
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                    <path d="M4 12a8 8 0 018-8" fill="currentColor" class="opacity-75"></path>
                </svg>
                <span>{{ $busy }}</span>
            </span>
        @endif
    </button>
@endif
