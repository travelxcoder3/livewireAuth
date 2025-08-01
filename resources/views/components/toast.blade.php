@props([
    'message' => session('message'),
    'type' => session('type', 'success'), // success, error, warning, info
])

@php
    $bgColors = [
        'success' => 'bg-[rgb(var(--primary-600))] text-white',
          'error' => 'bg-red-600 text-white',
        'warning' => 'bg-[rgb(var(--warning-400),255,193,7)] text-black',
        'info' => 'bg-[rgb(var(--info-600),59,130,246)] text-white',
    ];

    $svgs = [
        'success' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
        'error' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>',
        'warning' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 5a7 7 0 00-7 7v1a7 7 0 0014 0v-1a7 7 0 00-7-7z" /></svg>',
        'info' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 1010 10A10 10 0 0012 2z" /></svg>',
    ];
@endphp

@if ($message)
    <div 
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition
        class="fixed top-6 right-6 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2 {{ $bgColors[$type] ?? 'bg-[rgb(var(--primary-600))] text-white' }}"
    >
        <span class="w-5 h-5" aria-hidden="true">{!! $svgs[$type] ?? $svgs['success'] !!}</span>
        <span class="flex-1">{{ $message }}</span>
    </div>
@endif
